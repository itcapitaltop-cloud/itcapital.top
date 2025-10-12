<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerClosure, PartnerLevelPercent, Transaction, User
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompareRegularPremiumLogicCommand extends Command
{
    protected $signature = 'regular-premium:compare-logic
                            {--user= : ID пользователя для сравнения}
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}';

    protected $description = 'Сравнение старой (неправильной) и новой (исправленной) логики расчета регулярной премии';

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

        $this->info("=== СРАВНЕНИЕ ЛОГИКИ РАСЧЕТА РЕГУЛЯРНОЙ ПРЕМИИ ===");
        $this->info("Период: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->info("Пользователь: {$user->username} (ID: {$userId}, Ранг: {$user->rank})");
            }
        }

        // Собираем данные по старой логике
        $oldLogicData = $this->collectOldLogicData($userId);

        // Собираем данные по новой логике
        $newLogicData = $this->collectNewLogicData($userId);

        // Сравниваем результаты
        $this->compareResults($oldLogicData, $newLogicData);

        return self::SUCCESS;
    }

    private function collectOldLogicData(?int $userId): array
    {
        $this->info("\n=== СТАРАЯ ЛОГИКА (С РЕИНВЕСТАМИ) ===");

        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
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
            ->when($userId, fn($q) => $q->where('transactions.user_id', $userId))
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
                'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
                'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
            ])
            ->get();

        $net = [];
        $details = [];

        foreach ($packages as $pkg) {
            $user_id = $pkg->user_id;

            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');
            $reinvests = $pkg->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $pkg->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $pkg->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            // СТАРАЯ ФОРМУЛА (НЕПРАВИЛЬНАЯ)
            $oldNet = ($dividends - $dividendsWithdraw) + ($reinvests - $reinvestsWithdraw);

            $net[$user_id] = ($net[$user_id] ?? 0) + $oldNet;

            $details[$user_id] = [
                'dividends' => $dividends,
                'dividends_withdraw' => $dividendsWithdraw,
                'reinvests' => $reinvests,
                'reinvests_withdraw' => $reinvestsWithdraw,
                'old_net' => $oldNet
            ];
        }

        $this->info("Пользователи со старой логикой:");
        foreach ($net as $uid => $amount) {
            $user = User::find($uid);
            $username = $user ? $user->username : 'Unknown';
            $this->info("  - {$username} (ID: {$uid}): {$amount} ITC");

            if (isset($details[$uid])) {
                $d = $details[$uid];
                $this->line("    Дивиденды: {$d['dividends']}, Выводы: {$d['dividends_withdraw']}");
                $this->line("    Реинвесты: {$d['reinvests']}, Выводы реинвестов: {$d['reinvests_withdraw']}");
            }
        }

        return ['net' => $net, 'details' => $details];
    }

    private function collectNewLogicData(?int $userId): array
    {
        $this->info("\n=== НОВАЯ ЛОГИКА (БЕЗ РЕИНВЕСТОВ) ===");

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
        $details = [];

        foreach ($packages as $pkg) {
            $user_id = $pkg->user_id;

            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');

            // НОВАЯ ФОРМУЛА (ПРАВИЛЬНАЯ) - БЕЗ реинвестов
            $newNet = $dividends - $dividendsWithdraw;

            $net[$user_id] = ($net[$user_id] ?? 0) + $newNet;

            $details[$user_id] = [
                'dividends' => $dividends,
                'dividends_withdraw' => $dividendsWithdraw,
                'new_net' => $newNet
            ];
        }

        $this->info("Пользователи с новой логикой:");
        foreach ($net as $uid => $amount) {
            $user = User::find($uid);
            $username = $user ? $user->username : 'Unknown';
            $this->info("  - {$username} (ID: {$uid}): {$amount} ITC");

            if (isset($details[$uid])) {
                $d = $details[$uid];
                $this->line("    Дивиденды: {$d['dividends']}, Выводы: {$d['dividends_withdraw']}");
            }
        }

        return ['net' => $net, 'details' => $details];
    }

    private function compareResults(array $oldData, array $newData): void
    {
        $this->info("\n=== СРАВНЕНИЕ РЕЗУЛЬТАТОВ ===");

        $oldNet = $oldData['net'];
        $newNet = $newData['net'];

        $allUsers = array_unique(array_merge(array_keys($oldNet), array_keys($newNet)));

        $totalOld = 0;
        $totalNew = 0;
        $differences = [];

        foreach ($allUsers as $userId) {
            $oldAmount = $oldNet[$userId] ?? 0;
            $newAmount = $newNet[$userId] ?? 0;
            $difference = $oldAmount - $newAmount;

            $totalOld += $oldAmount;
            $totalNew += $newAmount;

            if (abs($difference) > 0.01) {
                $differences[$userId] = $difference;

                $user = User::find($userId);
                $username = $user ? $user->username : 'Unknown';

                $this->warn("Пользователь {$username} (ID: {$userId}):");
                $this->warn("  Старая логика: {$oldAmount} ITC");
                $this->warn("  Новая логика: {$newAmount} ITC");
                $this->warn("  Разница: {$difference} ITC");
            }
        }

        $this->info("\n=== ИТОГОВОЕ СРАВНЕНИЕ ===");
        $this->info("Общая сумма по старой логике: {$totalOld} ITC");
        $this->info("Общая сумма по новой логике: {$totalNew} ITC");
        $this->info("Общая разница: " . ($totalOld - $totalNew) . " ITC");

        if (!empty($differences)) {
            $this->error("\n🚨 ОБНАРУЖЕНЫ КРИТИЧЕСКИЕ РАСХОЖДЕНИЯ!");
            $this->error("Пользователей с разными результатами: " . count($differences));

            $this->info("\nРекомендации:");
            $this->info("1. СРОЧНО откатить неправильные начисления");
            $this->info("2. Пересчитать с исправленной логикой");
            $this->info("3. Проверить финансовые последствия");
        } else {
            $this->info("\n✅ Расхождений не обнаружено");
        }
    }
}
