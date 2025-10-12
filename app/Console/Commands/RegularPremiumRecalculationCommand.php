<?php

namespace App\Console\Commands;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Helpers\Notify;
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerClosure, PartnerLevelPercent, PartnerReward,
    Transaction, User
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegularPremiumRecalculationCommand extends Command
{
    protected $signature = 'regular-premium:recalculation
                            {--from-date= : Дата начала перерасчета (YYYY-MM-DD)}
                            {--to-date= : Дата окончания перерасчета (YYYY-MM-DD)}
                            {--user= : ID пользователя для перерасчета}
                            {--dry-run : Только показать расхождения без изменений}
                            {--force : Принудительно выполнить перерасчет}';

    protected $description = 'Перерасчет регулярных премий с момента завоза обновления по партнерской программе';

    private Carbon $from;
    private Carbon $to;
    private bool $dryRun;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        // Устанавливаем даты перерасчета
        $this->from = $this->option('from-date')
            ? Carbon::parse($this->option('from-date'))->startOfDay()
            : Carbon::parse('2025-07-18')->startOfDay(); // Дата завоза обновления по партнерской программе

        $this->to = $this->option('to-date')
            ? Carbon::parse($this->option('to-date'))->endOfDay()
            : now()->endOfDay();

        $onlyUser = $this->option('user') ? intval($this->option('user')) : null;

        if (!$this->dryRun && !$this->option('force')) {
            $this->error('Для выполнения перерасчета используйте флаг --force');
            $this->info('Для предварительного просмотра используйте --dry-run');
            return self::FAILURE;
        }

        $this->info("Перерасчет регулярных премий с {$this->from->format('Y-m-d')} по {$this->to->format('Y-m-d')}");

        if ($this->dryRun) {
            $this->warn("РЕЖИМ ПРОСМОТРА: изменения не будут сохранены");
        }

        // Получаем всех пользователей для перерасчета
        $users = $onlyUser ? collect([User::find($onlyUser)]) : User::whereNull('banned_at')->get();

        $totalDiscrepancies = 0;
        $totalOverpaid = 0;
        $totalUnderpaid = 0;

        foreach ($users as $user) {
            if (!$user) continue;

            $this->line("Обработка пользователя {$user->id} ({$user->username})");

            $discrepancies = $this->recalculateUserRegularPremium($user->id);

            if (!empty($discrepancies)) {
                $totalDiscrepancies += count($discrepancies);

                foreach ($discrepancies as $discrepancy) {
                    if ($discrepancy['type'] === 'overpaid') {
                        $totalOverpaid += $discrepancy['amount'];
                        $this->warn("  Переначислено: {$discrepancy['amount']} ITC за период {$discrepancy['period']}");
                    } else {
                        $totalUnderpaid += $discrepancy['amount'];
                        $this->info("  Недоначислено: {$discrepancy['amount']} ITC за период {$discrepancy['period']}");
                    }
                }
            }
        }

        $this->info("\nИтого расхождений: {$totalDiscrepancies}");
        $this->warn("Переначислено: {$totalOverpaid} ITC");
        $this->info("Недоначислено: {$totalUnderpaid} ITC");

        return self::SUCCESS;
    }

    private function recalculateUserRegularPremium(int $userId): array
    {
        $discrepancies = [];

        // Получаем все периоды начисления регулярной премии для пользователя
        $accruedPeriods = $this->getAccruedPeriods($userId);

        foreach ($accruedPeriods as $period) {
            $calculated = $this->calculateRegularPremiumForPeriod($userId, $period['from'], $period['to']);
            $actual = $period['amount'];

            $difference = $calculated - $actual;

            if (abs($difference) > 0.01) { // Учитываем погрешность округления
                $discrepancy = [
                    'period' => $period['from']->format('Y-m-d') . ' - ' . $period['to']->format('Y-m-d'),
                    'calculated' => $calculated,
                    'actual' => $actual,
                    'difference' => $difference,
                    'type' => $difference > 0 ? 'underpaid' : 'overpaid',
                    'amount' => abs($difference)
                ];

                $discrepancies[] = $discrepancy;

                // Обрабатываем переначисление
                if ($difference < 0 && !$this->dryRun) {
                    $this->handleOverpayment($userId, abs($difference), $period['from'], $period['to']);
                }
            }
        }

        return $discrepancies;
    }

    private function getAccruedPeriods(int $userId): array
    {
        // Получаем все начисления регулярной премии для пользователя за период
        $transactions = Transaction::where('user_id', $userId)
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->orderBy('accepted_at')
            ->get();

        $periods = [];

        foreach ($transactions as $transaction) {
            // Определяем период начисления (14 дней назад от даты начисления)
            $to = $transaction->accepted_at->copy()->startOfDay();
            $from = $to->copy()->subDays(14);

            $periods[] = [
                'from' => $from,
                'to' => $to,
                'amount' => (float) $transaction->amount,
                'transaction_id' => $transaction->id
            ];
        }

        return $periods;
    }

    private function calculateRegularPremiumForPeriod(int $userId, Carbon $from, Carbon $to): float
    {
        // Получаем всех потомков пользователя
        $descendantIds = PartnerClosure::where('ancestor_id', $userId)
            ->whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'))
            ->pluck('descendant_id', 'depth');

        if ($descendantIds->isEmpty()) {
            return 0;
        }

        // Собираем чистую прибыль по пользователям за период
        $descendantsNet = $this->collectNetProfitPerUserForPeriod($from, $to);

        $totalReward = 0;

        foreach ($descendantIds as $depth => $descendantId) {
            if (!isset($descendantsNet[$descendantId])) continue;

            $net = $descendantsNet[$descendantId];
            if ($net <= 0) continue;

            // Проверяем, что у пользователя ранг больше 0
            $user = User::find($userId);
            if (!$user || $user->rank <= 0) continue;

            $percent = $this->percentForAncestor($userId, $depth);
            if ($percent <= 0) continue;

            $reward = round($net * $percent / 100, 2);
            $totalReward += $reward;
        }

        return $totalReward;
    }

    private function collectNetProfitPerUserForPeriod(Carbon $from, Carbon $to): array
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->where(function ($q) use ($from, $to) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$from, $to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$from, $to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$from, $to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$from, $to]));
            })
            ->with([
                'profits' => fn ($q) =>
                $q->whereBetween('package_profits.created_at', [$from, $to]),
                'reinvestProfitsAll' => fn ($q) =>
                $q->whereBetween('package_profit_reinvests.created_at', [$from, $to]),
                'withdrawProfitsTransactions' => fn ($q) =>
                $q->whereBetween('transactions.accepted_at', [$from, $to]),
                'reinvestProfitWithdraws' => fn ($q) =>
                $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$from, $to]),
            ])
            ->get();

        $net = [];

        foreach ($packages as $pkg) {
            $user_id = $pkg->user_id;

            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');
            $reinvests = $pkg->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $pkg->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $pkg->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $net[$user_id] = ($net[$user_id] ?? 0)
                + ($dividends - $dividendsWithdraw)
                + ($reinvests - $reinvestsWithdraw);
        }

        return $net;
    }

    private function percentForAncestor(int $ancestorId, int $line): float
    {
        $user = User::find($ancestorId);
        $level = $user?->rank;

        if ($line >= 6 && $line <= 20) {
            if (!$user->extended_lines) {
                return 0;
            }
            return (float) PartnerLevelPercent::where([
                'partner_level_id' => $level,
                'bonus_type' => 'regular',
                'line' => $line,
            ])->value('percent');
        }

        $override = DB::table('user_level_percent_overrides')
            ->where('user_id', $ancestorId)
            ->where('partner_level_id', $level)
            ->where('bonus_type', 'regular')
            ->where('line', $line)
            ->value('percent');

        if (!is_null($override)) return (float) $override;

        return (float) PartnerLevelPercent::where([
            'partner_level_id' => $level,
            'bonus_type' => 'regular',
            'line' => $line,
        ])->value('percent');
    }

    private function handleOverpayment(int $userId, float $overpaidAmount, Carbon $from, Carbon $to): void
    {
        try {
            DB::transaction(function () use ($userId, $overpaidAmount, $from, $to) {
                // Списываем переначисленную сумму с основного баланса
                $transactionRepo = app(TransactionRepositoryContract::class);

                $transactionRepo->commonStore(
                    new CreateTransactionDto(
                        userId: $userId,
                        trxType: TrxTypeEnum::PARTNER_BONUS_ROLLBACK,
                        balanceType: BalanceTypeEnum::MAIN,
                        amount: $overpaidAmount,
                        acceptedAt: now(),
                        prefix: 'RB-',
                    )
                );

                // Отправляем уведомление пользователю
                $user = User::find($userId);
                if ($user) {
                    $message = "Уважаемый инвестор! В связи с техническим сбоем мы ошибочно начислили вам суммарно {$overpaidAmount} ITC регулярной премии сверх положенного за последние два месяца. Эта сумма списана с вашего основного баланса.";

                    $user->notify(new \App\Notifications\InAppNotification(
                        title: 'Корректировка регулярной премии',
                        message: $message,
                        icon: 'notifications/deposit-approved.svg'
                    ));
                }
            });

            $this->info("  Обработано переначисление: {$overpaidAmount} ITC списано с основного баланса");

        } catch (\Exception $e) {
            $this->error("  Ошибка при обработке переначисления для пользователя {$userId}: " . $e->getMessage());
            Log::error("Regular premium overpayment handling failed", [
                'user_id' => $userId,
                'amount' => $overpaidAmount,
                'error' => $e->getMessage()
            ]);
        }
    }
}
