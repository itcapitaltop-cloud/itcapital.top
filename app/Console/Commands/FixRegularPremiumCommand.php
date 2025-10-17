<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
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

class FixRegularPremiumCommand extends Command
{
    protected $signature = 'regular-premium:fix
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--user= : ID пользователя для исправления}
                            {--dry-run : Только показать что будет исправлено}
                            {--force : Принудительно выполнить исправление}';

    protected $description = 'ПОЛНОЕ ИСПРАВЛЕНИЕ регулярной премии: откат неправильных начислений и пересчет с правильной логикой';

    private Carbon $from;
    private Carbon $to;
    private bool $dryRun;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if (!$this->dryRun && !$this->option('force')) {
            $this->error('Для выполнения исправления используйте флаг --force');
            $this->info('Для предварительного просмотра используйте --dry-run');
            return self::FAILURE;
        }

        $this->from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->subDays(14)->startOfDay();

        $this->to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : now()->endOfDay();

        $userId = $this->option('user') ? intval($this->option('user')) : null;

        $this->info("=== ПОЛНОЕ ИСПРАВЛЕНИЕ РЕГУЛЯРНОЙ ПРЕМИИ ===");
        $this->info("Период: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        if ($this->dryRun) {
            $this->warn("РЕЖИМ ПРОСМОТРА: изменения не будут сохранены");
        }

        // Шаг 1: Анализ текущей ситуации
        $this->analyzeCurrentSituation($userId);

        // Шаг 2: Откат неправильных начислений
        $this->rollbackIncorrectAccruals($userId);

        // Шаг 3: Пересчет с правильной логикой
        $this->recalculateWithCorrectLogic($userId);

        // Шаг 4: Итоговый отчет
        $this->generateFinalReport($userId);

        return self::SUCCESS;
    }

    private function analyzeCurrentSituation(?int $userId): void
    {
        $this->info("\n=== ШАГ 1: АНАЛИЗ ТЕКУЩЕЙ СИТУАЦИИ ===");

        // Находим все неправильные начисления
        $incorrectAccruals = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with('user')
            ->get();

        $this->info("Найдено неправильных начислений: {$incorrectAccruals->count()}");
        $totalIncorrect = $incorrectAccruals->sum('amount');
        $this->info("Общая сумма неправильных начислений: {$totalIncorrect} ITC");

        foreach ($incorrectAccruals as $accrual) {
            $this->line("  - {$accrual->user->username} (ID: {$accrual->user_id}): {$accrual->amount} ITC");
        }

        // Анализируем источники проблем
        $this->analyzeProblemSources($userId);
    }

    private function analyzeProblemSources(?int $userId): void
    {
        $this->info("\nАнализ источников проблем:");

        // Находим пользователей с большими реинвестами
        $usersWithReinvests = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->join('package_profit_reinvests', 'itc_packages.uuid', '=', 'package_profit_reinvests.package_uuid')
            ->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('transactions.user_id', $userId))
            ->select('transactions.user_id', DB::raw('SUM(package_profit_reinvests.amount) as total_reinvests'))
            ->groupBy('transactions.user_id')
            ->havingRaw('SUM(package_profit_reinvests.amount) > 100')
            ->orderByRaw('SUM(package_profit_reinvests.amount) DESC')
            ->get();

        $this->info("Пользователи с большими реинвестами (>100 ITC):");
        foreach ($usersWithReinvests as $userReinvest) {
            $user = User::find($userReinvest->user_id);
            $username = $user ? $user->username : 'Unknown';
            $this->warn("  - {$username} (ID: {$userReinvest->user_id}): {$userReinvest->total_reinvests} ITC реинвестов");
        }
    }

    private function rollbackIncorrectAccruals(?int $userId): void
    {
        $this->info("\n=== ШАГ 2: ОТКАТ НЕПРАВИЛЬНЫХ НАЧИСЛЕНИЙ ===");

        $transactions = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get();

        if ($transactions->isEmpty()) {
            $this->info("Нет начислений для отката");
            return;
        }

        $trxUuids = $transactions->pluck('uuid');
        $totalAmount = $transactions->sum('amount');

        $this->info("Будет откачено начислений: {$transactions->count()}");
        $this->info("Общая сумма для отката: {$totalAmount} ITC");

        if ($this->dryRun) {
            $this->warn("DRY-RUN: Откат не выполнен");
            return;
        }

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

        $this->info("✅ Откат неправильных начислений завершен");
    }

    private function recalculateWithCorrectLogic(?int $userId): void
    {
        $this->info("\n=== ШАГ 3: ПЕРЕСЧЕТ С ПРАВИЛЬНОЙ ЛОГИКОЙ ===");

        // ИСПРАВЛЕННЫЙ расчет чистой прибыли (БЕЗ реинвестов)
        $descendantsNet = $this->collectCorrectedNetProfitPerUser($userId);

        $rewards = [];
        $details = [];

        $closure = PartnerClosure::whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'));

        $closure->orderBy('depth')
            ->each(function ($row) use ($descendantsNet, &$rewards, &$details) {

                $descId = $row->descendant_id;
                $ancestor = $row->ancestor_id;
                $line = $row->depth;

                if (!isset($descendantsNet[$descId])) return;
                $net = $descendantsNet[$descId];
                if ($net <= 0) return;

                // Проверяем, что у пользователя ранг больше 0
                $user = User::find($ancestor);
                if (!$user || $user->rank <= 0) return;

                $percent = $this->percentForAncestor($ancestor, $line);
                if ($percent <= 0) return;

                $reward = round($net * $percent / 100, 2);

                if ($reward <= 0) return;

                $rewards[$ancestor] = ($rewards[$ancestor] ?? 0) + $reward;
                $details[$ancestor][] = [$descId, $line, $reward];
            });

        $this->info("Будет начислено правильных премий: " . count($rewards));
        $totalCorrect = array_sum($rewards);
        $this->info("Общая сумма правильных начислений: {$totalCorrect} ITC");

        foreach ($rewards as $userId => $sum) {
            $user = User::find($userId);
            $username = $user ? $user->username : 'Unknown';
            $this->line("  - {$username} (ID: {$userId}): {$sum} ITC");
        }

        if ($this->dryRun) {
            $this->warn("DRY-RUN: Начисления не выполнены");
            return;
        }

        // Выполняем правильные начисления
        foreach ($rewards as $userId => $sum) {
            DB::transaction(function () use ($userId, $sum, $details) {

                $trxUuid = 'RP-'.Str::random(10);

                Transaction::create([
                    'uuid' => $trxUuid,
                    'user_id' => $userId,
                    'amount' => $sum,
                    'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL,
                    'balance_type' => BalanceTypeEnum::PARTNER,
                    'accepted_at' => now(),
                ]);

                foreach ($details[$userId] as [$descId, $line, $amount]) {
                    PartnerReward::create([
                        'uuid' => $trxUuid,
                        'from_user_id' => $descId,
                        'reward_type' => PartnerRewardTypeEnum::REGULAR->value,
                        'line' => $line,
                        'amount' => $amount,
                        'trx_uuid' => $trxUuid,
                    ]);
                }
            });
        }

        $this->info("✅ Пересчет с правильной логикой завершен");
    }

    private function generateFinalReport(?int $userId): void
    {
        $this->info("\n=== ШАГ 4: ИТОГОВЫЙ ОТЧЕТ ===");

        $finalAccruals = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with('user')
            ->get();

        $this->info("Итоговые начисления: {$finalAccruals->count()}");
        $totalFinal = $finalAccruals->sum('amount');
        $this->info("Общая сумма итоговых начислений: {$totalFinal} ITC");

        $this->info("\n✅ ИСПРАВЛЕНИЕ ЗАВЕРШЕНО");
        $this->info("Теперь регулярная премия рассчитывается БЕЗ учета реинвестов");
        $this->info("Это исправляет критическую ошибку в логике начисления");
    }

    private function collectCorrectedNetProfitPerUser(?int $userId): array
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->where(function ($q) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$this->from, $this->to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]));
            })
            ->when($userId, fn($q) => $q->where('transactions.user_id', $userId))
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
            ])
            ->get();

        $net = [];

        foreach ($packages as $pkg) {
            $user_id = $pkg->user_id;

            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');

            // ИСПРАВЛЕННАЯ формула: только дивиденды минус их выводы
            $net[$user_id] = ($net[$user_id] ?? 0) + ($dividends - $dividendsWithdraw);
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
}
