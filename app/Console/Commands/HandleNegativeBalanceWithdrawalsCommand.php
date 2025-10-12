<?php

namespace App\Console\Commands;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Helpers\Notify;
use App\Models\{Transaction, User, Withdraw};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleNegativeBalanceWithdrawalsCommand extends Command
{
    protected $signature = 'withdrawals:handle-negative-balance
                            {--dry-run : Только показать проблемные запросы без изменений}
                            {--force : Принудительно выполнить обработку}';

    protected $description = 'Обработка запросов на вывод при отрицательном или недостаточном балансе';

    private bool $dryRun;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if (!$this->dryRun && !$this->option('force')) {
            $this->error('Для выполнения обработки используйте флаг --force');
            $this->info('Для предварительного просмотра используйте --dry-run');
            return self::FAILURE;
        }

        if ($this->dryRun) {
            $this->warn("РЕЖИМ ПРОСМОТРА: изменения не будут сохранены");
        }

        $this->info('Поиск запросов на вывод с проблемными балансами...');

        // Находим все активные запросы на вывод
        $withdrawals = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->whereNull('transactions.accepted_at')
            ->whereNull('transactions.rejected_at')
            ->select([
                'withdraws.uuid',
                'transactions.user_id',
                'transactions.amount',
                'transactions.created_at',
                'withdraws.commission'
            ])
            ->get();

        $problematicCount = 0;
        $totalAmount = 0;

        foreach ($withdrawals as $withdrawal) {
            $userId = $withdrawal->user_id;
            $requestedAmount = (float) $withdrawal->amount;
            $commission = (float) $withdrawal->commission;
            $totalToWithdraw = $requestedAmount + $commission;

            // Получаем текущий баланс пользователя
            $transactionRepo = app(TransactionRepositoryContract::class);
            $currentBalance = (float) $transactionRepo->getBalanceAmountByUserIdAndType($userId, BalanceTypeEnum::MAIN);

            $this->line("Пользователь {$userId}: запрошено {$totalToWithdraw} ITC, баланс: {$currentBalance} ITC");

            if ($currentBalance < $totalToWithdraw) {
                $problematicCount++;
                $totalAmount += $totalToWithdraw - $currentBalance;

                $this->warn("  ❌ Недостаточно средств: не хватает " . ($totalToWithdraw - $currentBalance) . " ITC");

                if (!$this->dryRun) {
                    $this->handleInsufficientBalance($withdrawal, $currentBalance, $totalToWithdraw);
                }
            } else {
                $this->info("  ✅ Достаточно средств");
            }
        }

        $this->info("\nИтого проблемных запросов: {$problematicCount}");
        $this->warn("Общая сумма недостающих средств: {$totalAmount} ITC");

        return self::SUCCESS;
    }

    private function handleInsufficientBalance($withdrawal, float $currentBalance, float $requiredAmount): void
    {
        try {
            DB::transaction(function () use ($withdrawal, $currentBalance, $requiredAmount) {
                $userId = $withdrawal->user_id;
                $shortage = $requiredAmount - $currentBalance;

                // Отклоняем запрос на вывод
                Transaction::where('uuid', $withdrawal->uuid)
                    ->update(['rejected_at' => now()]);

                // Отправляем уведомление пользователю
                $user = User::find($userId);
                if ($user) {
                    $message = "Ваш запрос на вывод {$withdrawal->amount} ITC отклонен из-за недостаточного баланса. Текущий баланс: {$currentBalance} ITC. Необходимо: {$requiredAmount} ITC (включая комиссию).";

                    $user->notify(new \App\Notifications\InAppNotification(
                        title: 'Запрос на вывод отклонен',
                        message: $message,
                        icon: 'notifications/withdraw-rejected.svg'
                    ));
                }

                $this->info("  Обработан запрос пользователя {$userId}: отклонен");
            });

        } catch (\Exception $e) {
            $this->error("  Ошибка при обработке запроса пользователя {$withdrawal->user_id}: " . $e->getMessage());
            Log::error("Negative balance withdrawal handling failed", [
                'withdrawal_uuid' => $withdrawal->uuid,
                'user_id' => $withdrawal->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
