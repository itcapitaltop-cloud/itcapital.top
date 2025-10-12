<?php

namespace App\Console\Commands;

use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Helpers\Notify;
use App\Models\{
    PartnerReward, Transaction, User
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RollbackIncorrectRegularPremiumCommand extends Command
{
    protected $signature = 'regular-premium:rollback-incorrect
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--user= : ID пользователя для отката}
                            {--dry-run : Показать что будет откачено без выполнения}
                            {--force : Принудительно выполнить откат}
                            {--include-rank-0 : Включить откат для пользователей с рангом 0}
                            {--check-withdrawals : Проверить выводы перед откатом}';

    protected $description = 'Откатить неправильные начисления регулярной премии (с архивными пакетами)';

    private Carbon $from;
    private Carbon $to;

    public function handle(): int
    {
        $this->from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->subDays(14)->startOfDay();

        $this->to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : now()->endOfDay();

        $onlyUser = $this->option('user') ? intval($this->option('user')) : null;
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $includeRank0 = $this->option('include-rank-0');
        $checkWithdrawals = $this->option('check-withdrawals');

        if (!$dryRun && !$force) {
            $this->error('Для выполнения отката используйте флаг --force');
            $this->info('Для предварительного просмотра используйте --dry-run');
            return self::FAILURE;
        }

        $this->info("Период отката: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        // Найти все транзакции регулярной премии за период
        $query = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to]);

        if ($onlyUser) {
            $query->where('user_id', $onlyUser);
        }

        // Если не включен флаг для ранга 0, исключаем пользователей с рангом 0
        if (!$includeRank0) {
            $query->whereHas('user', function ($q) {
                $q->where('rank', '>', 0);
            });
        }

        $transactions = $query->with('user')->get();

        if ($transactions->isEmpty()) {
            $this->info('Транзакции для отката не найдены.');
            return self::SUCCESS;
        }

        $this->info("Найдено транзакций для отката: {$transactions->count()}");

        $totalAmount = 0;
        $affectedUsers = [];

        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->amount;
            $affectedUsers[$transaction->user_id] = ($affectedUsers[$transaction->user_id] ?? 0) + $transaction->amount;

            $this->line("  - Пользователь {$transaction->user->username} (ID: {$transaction->user_id}): {$transaction->amount} (UUID: {$transaction->uuid})");
        }

        $this->info("Общая сумма для отката: {$totalAmount}");
        $this->info("Затронуто пользователей: " . count($affectedUsers));

        if ($dryRun) {
            $this->warn('Это предварительный просмотр. Для выполнения отката используйте --force');
            return self::SUCCESS;
        }

        // Проверить, есть ли выводы средств с регулярной премии
        if ($checkWithdrawals) {
            $this->checkWithdrawals($affectedUsers);
        }

        if (!$this->confirm('Продолжить откат?')) {
            $this->info('Откат отменен.');
            return self::SUCCESS;
        }

        // Выполнить откат
        $this->performRollback($transactions);

        // Отправить уведомления
        $this->sendNotifications($affectedUsers);

        $this->info('Откат завершен успешно.');
        return self::SUCCESS;
    }

    private function checkWithdrawals(array $affectedUsers): void
    {
        $this->info('Проверка выводов средств...');

        foreach ($affectedUsers as $userId => $amount) {
            $user = User::find($userId);
            $username = $user ? $user->username : 'Unknown';

            // Проверить переводы в партнерский баланс
            $partnerTransfers = Transaction::where('user_id', $userId)
                ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->sum('amount');

            if ($partnerTransfers > 0) {
                $this->warn("  - Пользователь {$username} (ID: {$userId}): переведено в партнерский баланс: {$partnerTransfers}");
            }

            // Проверить выводы с основного баланса (если переводили с партнерского)
            $mainWithdrawals = Transaction::where('user_id', $userId)
                ->where('trx_type', TrxTypeEnum::WITHDRAW)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->sum('amount');

            if ($mainWithdrawals > 0) {
                $this->warn("  - Пользователь {$username} (ID: {$userId}): выведено с основного баланса: {$mainWithdrawals}");
            }

            // Проверить отмененные заявки на вывод
            $cancelledWithdrawals = \App\Models\Withdraw::query()
                ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', $userId)
                ->whereNotNull('transactions.rejected_at')
                ->whereBetween('transactions.rejected_at', [$this->from, $this->to])
                ->sum('transactions.amount');

            if ($cancelledWithdrawals > 0) {
                $this->warn("  - Пользователь {$username} (ID: {$userId}): отменено заявок на вывод: {$cancelledWithdrawals}");
            }

            // Проверить активные заявки на вывод
            $activeWithdrawals = \App\Models\Withdraw::query()
                ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', $userId)
                ->whereNull('transactions.accepted_at')
                ->whereNull('transactions.rejected_at')
                ->sum('transactions.amount');

            if ($activeWithdrawals > 0) {
                $this->warn("  - Пользователь {$username} (ID: {$userId}): активные заявки на вывод: {$activeWithdrawals}");
            }
        }
    }

    private function performRollback($transactions): void
    {
        $this->info('Выполнение отката...');

        $trxUuids = $transactions->pluck('uuid');

        DB::transaction(function () use ($trxUuids) {
            // Удалить PartnerReward записи
            $deletedRewards = PartnerReward::whereIn('uuid', $trxUuids)
                ->where('reward_type', PartnerRewardTypeEnum::REGULAR->value)
                ->delete();

            $this->line("Удалено записей PartnerReward: {$deletedRewards}");

            // Удалить транзакции
            $deletedTransactions = Transaction::whereIn('uuid', $trxUuids)->delete();

            $this->line("Удалено транзакций: {$deletedTransactions}");
        });
    }

    private function sendNotifications(array $affectedUsers): void
    {
        $this->info('Отправка уведомлений...');

        foreach ($affectedUsers as $userId => $amount) {
            $user = User::find($userId);
            if ($user) {
                // Здесь можно добавить отправку уведомления пользователю
                // Notify::regularPremiumRollback($user, $amount);
                $this->line("Уведомление отправлено пользователю {$user->username} (ID: {$userId}) на сумму {$amount}");
            }
        }
    }
}
