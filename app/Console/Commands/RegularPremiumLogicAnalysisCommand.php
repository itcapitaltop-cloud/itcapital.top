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

class RegularPremiumLogicAnalysisCommand extends Command
{
    protected $signature = 'regular-premium:logic-analysis
                            {--user= : ID пользователя для детального анализа}
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--check-reinvests : Детально проверить логику реинвестов}
                            {--check-percentages : Проверить проценты по линиям}';

    protected $description = 'Детальный анализ логики начисления регулярной премии';

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

        $userId = $this->option('user') ? intval($this->option('user')) : null;
        $checkReinvests = $this->option('check-reinvests');
        $checkPercentages = $this->option('check-percentages');

        $this->info("=== АНАЛИЗ ЛОГИКИ НАЧИСЛЕНИЯ РЕГУЛЯРНОЙ ПРЕМИИ ===");
        $this->info("Период: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->info("Пользователь: {$user->username} (ID: {$userId}, Ранг: {$user->rank})");
            }
        }

        // 1. Анализ общей логики
        $this->analyzeGeneralLogic($userId);

        // 2. Детальный анализ реинвестов
        if ($checkReinvests) {
            $this->analyzeReinvestLogic($userId);
        }

        // 3. Проверка процентов
        if ($checkPercentages) {
            $this->analyzePercentages();
        }

        // 4. Анализ конкретных случаев
        $this->analyzeSpecificCases($userId);

        return self::SUCCESS;
    }

    private function analyzeGeneralLogic(?int $userId): void
    {
        $this->info("\n=== ОБЩАЯ ЛОГИКА НАЧИСЛЕНИЯ ===");

        $this->info("1. Система собирает чистую прибыль по пользователям за период");
        $this->info("2. Чистая прибыль = (Дивиденды - Выводы дивидендов) + (Реинвесты - Выводы реинвестов)");
        $this->info("3. Для каждого предка рассчитывается премия от прибыли его потомков");
        $this->info("4. Процент зависит от ранга предка и линии потомка");

        // Показываем пример расчета
        $this->showCalculationExample($userId);
    }

    private function showCalculationExample(?int $userId): void
    {
        $this->info("\n--- ПРИМЕР РАСЧЕТА ---");

        // Берем пользователя с наибольшей премией
        $topUser = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('amount', 'desc')
            ->first();

        if (!$topUser) {
            $this->info("Нет данных для примера");
            return;
        }

        $user = $topUser->user;
        $this->info("Пример для пользователя {$user->username} (ID: {$user->id}):");
        $this->info("Полученная премия: {$topUser->amount} ITC");

        // Показываем детали расчета
        $this->showUserCalculationDetails($user->id, $topUser->amount);
    }

    private function showUserCalculationDetails(int $userId, float $receivedAmount): void
    {
        $this->info("\nДетали расчета:");

        // Получаем всех потомков
        $descendantIds = PartnerClosure::where('ancestor_id', $userId)
            ->whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'))
            ->pluck('descendant_id', 'depth');

        if ($descendantIds->isEmpty()) {
            $this->info("  - Нет потомков для расчета");
            return;
        }

        // Собираем чистую прибыль по пользователям за период
        $descendantsNet = $this->collectNetProfitPerUserForPeriod($this->from, $this->to);

        $totalCalculated = 0;
        $this->info("  - Потомки и их вклад:");

        foreach ($descendantIds as $depth => $descendantId) {
            if (!isset($descendantsNet[$descendantId])) continue;

            $net = $descendantsNet[$descendantId];
            if ($net <= 0) continue;

            $user = User::find($userId);
            if (!$user || $user->rank <= 0) continue;

            $percent = $this->percentForAncestor($userId, $depth);
            if ($percent <= 0) continue;

            $reward = round($net * $percent / 100, 2);
            $totalCalculated += $reward;

            $descendant = User::find($descendantId);
            $descendantName = $descendant ? $descendant->username : 'Unknown';

            $this->info("    * {$descendantName} (ID: {$descendantId}, линия {$depth}):");
            $this->info("      Чистая прибыль: {$net} ITC");
            $this->info("      Процент: {$percent}%");
            $this->info("      Вклад в премию: {$reward} ITC");

            // Детали чистой прибыли
            $this->showNetProfitDetails($descendantId);
        }

        $this->info("  - Итого рассчитано: {$totalCalculated} ITC");
        $this->info("  - Получено: {$receivedAmount} ITC");
        $this->info("  - Разница: " . ($totalCalculated - $receivedAmount) . " ITC");
    }

    private function showNetProfitDetails(int $userId): void
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->where('transactions.user_id', $userId)
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->where(function ($q) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]));
            })
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
                'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
                'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
            ])
            ->get();

        $totalDividends = 0;
        $totalDividendsWithdraw = 0;
        $totalReinvests = 0;
        $totalReinvestsWithdraw = 0;

        foreach ($packages as $pkg) {
            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');
            $reinvests = $pkg->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $pkg->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $pkg->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $totalDividends += $dividends;
            $totalDividendsWithdraw += $dividendsWithdraw;
            $totalReinvests += $reinvests;
            $totalReinvestsWithdraw += $reinvestsWithdraw;
        }

        $this->info("      Детали чистой прибыли:");
        $this->info("        Дивиденды: {$totalDividends} ITC");
        $this->info("        Выводы дивидендов: {$totalDividendsWithdraw} ITC");
        $this->info("        Реинвесты: {$totalReinvests} ITC");
        $this->info("        Выводы реинвестов: {$totalReinvestsWithdraw} ITC");
        $this->info("        Чистая прибыль: " . (($totalDividends - $totalDividendsWithdraw) + ($totalReinvests - $totalReinvestsWithdraw)) . " ITC");
    }

    private function analyzeReinvestLogic(?int $userId): void
    {
        $this->info("\n=== АНАЛИЗ ЛОГИКИ РЕИНВЕСТОВ ===");

        $this->info("Проблема: В регулярную премию может записываться весь реинвест");
        $this->info("Проверяем логику расчета...");

        // Находим пользователей с большими реинвестами
        $usersWithReinvests = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->join('package_profit_reinvests', 'itc_packages.uuid', '=', 'package_profit_reinvests.package_uuid')
            ->where('transactions.user_id', '!=', 0)
            ->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to])
            ->when($userId, fn($q) => $q->where('transactions.user_id', $userId))
            ->select('transactions.user_id', DB::raw('SUM(package_profit_reinvests.amount) as total_reinvests'))
            ->groupBy('transactions.user_id')
            ->orderBy('total_reinvests', 'desc')
            ->limit(5)
            ->get();

        foreach ($usersWithReinvests as $userReinvest) {
            $user = User::find($userReinvest->user_id);
            $username = $user ? $user->username : 'Unknown';

            $this->info("\nПользователь {$username} (ID: {$userReinvest->user_id}):");
            $this->info("  Общие реинвесты за период: {$userReinvest->total_reinvests} ITC");

            // Проверяем, получал ли он регулярную премию
            $receivedPremium = Transaction::where('user_id', $userReinvest->user_id)
                ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->sum('amount');

            if ($receivedPremium > 0) {
                $this->warn("  ⚠️ Получил регулярную премию: {$receivedPremium} ITC");
                $this->warn("  ⚠️ Соотношение реинвест/премия: " . round($userReinvest->total_reinvests / $receivedPremium, 2));
            } else {
                $this->info("  ✅ НЕ получал регулярную премию");
            }

            // Проверяем, был ли он потомком
            $asDescendant = PartnerClosure::where('descendant_id', $userReinvest->user_id)
                ->whereBetween('depth', [1, 8])
                ->exists();

            if ($asDescendant) {
                $this->info("  ✅ Участвует как потомок в расчетах премий");
            } else {
                $this->info("  ❌ НЕ участвует как потомок");
            }
        }
    }

    private function analyzePercentages(): void
    {
        $this->info("\n=== АНАЛИЗ ПРОЦЕНТОВ ПО ЛИНИЯМ ===");

        $percentages = PartnerLevelPercent::where('bonus_type', 'regular')
            ->orderBy('partner_level_id')
            ->orderBy('line')
            ->get();

        $this->info("Проценты по рангам и линиям:");
        foreach ($percentages as $percent) {
            $this->info("  Ранг {$percent->partner_level_id}, линия {$percent->line}: {$percent->percent}%");
        }
    }

    private function analyzeSpecificCases(?int $userId): void
    {
        $this->info("\n=== АНАЛИЗ КОНКРЕТНЫХ СЛУЧАЕВ ===");

        // Находим случаи, где премия больше 100 ITC
        $largePremiums = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->where('amount', '>', 100)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with('user')
            ->get();

        foreach ($largePremiums as $premium) {
            $this->info("\nБольшая премия: {$premium->user->username} (ID: {$premium->user_id})");
            $this->info("  Сумма: {$premium->amount} ITC");
            $this->info("  Дата: {$premium->accepted_at->format('Y-m-d H:i:s')}");

            // Анализируем источники этой премии
            $this->analyzePremiumSources($premium->user_id, $premium->amount);
        }
    }

    private function analyzePremiumSources(int $userId, float $premiumAmount): void
    {
        // Получаем всех потомков
        $descendantIds = PartnerClosure::where('ancestor_id', $userId)
            ->whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'))
            ->pluck('descendant_id', 'depth');

        $descendantsNet = $this->collectNetProfitPerUserForPeriod($this->from, $this->to);

        $this->info("  Источники премии:");
        foreach ($descendantIds as $depth => $descendantId) {
            if (!isset($descendantsNet[$descendantId])) continue;

            $net = $descendantsNet[$descendantId];
            if ($net <= 0) continue;

            $percent = $this->percentForAncestor($userId, $depth);
            if ($percent <= 0) continue;

            $reward = round($net * $percent / 100, 2);

            $descendant = User::find($descendantId);
            $descendantName = $descendant ? $descendant->username : 'Unknown';

            $this->info("    - {$descendantName} (линия {$depth}): {$net} ITC × {$percent}% = {$reward} ITC");
        }
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
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$from, $to]),
                'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$from, $to]),
                'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$from, $to]),
                'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$from, $to]),
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
}
