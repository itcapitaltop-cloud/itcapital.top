<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerReward, Transaction, User
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeRegularPremiumIssueCommand extends Command
{
    protected $signature = 'regular-premium:analyze-issue
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--user= : ID пользователя для анализа}';

    protected $description = 'Анализ проблемы с начислением регулярной премии (архивные пакеты)';

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

        $this->info("Анализ периода: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        // 1. Анализ архивных пакетов, которые участвовали в расчете
        $this->analyzeArchivedPackages($onlyUser);

        // 2. Анализ начислений регулярной премии
        $this->analyzeRegularPremiumAccruals($onlyUser);

        // 3. Сравнение с правильным расчетом
        $this->compareWithCorrectCalculation($onlyUser);

        return self::SUCCESS;
    }

    private function analyzeArchivedPackages(?int $onlyUser): void
    {
        $this->info("\n=== АНАЛИЗ АРХИВНЫХ ПАКЕТОВ ===");

        $query = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('itc_packages.type', PackageTypeEnum::ARCHIVE)
            ->where(function ($q) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]));
            });

        if ($onlyUser) {
            $query->where('transactions.user_id', $onlyUser);
        }

        $archivedPackages = $query->with([
            'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
            'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
            'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
            'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
            'transaction'
        ])->get();

        $this->info("Найдено архивных пакетов с активностью: {$archivedPackages->count()}");

        $totalIncorrectProfit = 0;
        $userStats = [];

        foreach ($archivedPackages as $package) {
            $user_id = $package->user_id;
            $user = $package->transaction->user ?? User::find($user_id);

            $dividends = $package->profits->sum('amount');
            $dividendsWithdraw = $package->withdrawProfitsTransactions->sum('amount');
            $reinvests = $package->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $package->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $package->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $netProfit = ($dividends - $dividendsWithdraw) + ($reinvests - $reinvestsWithdraw);

            if ($netProfit > 0) {
                $totalIncorrectProfit += $netProfit;
                $userStats[$user_id] = ($userStats[$user_id] ?? 0) + $netProfit;

                $username = $user ? $user->username : 'Unknown';
                $this->line("  - Пакет {$package->uuid} (пользователь {$username}): {$netProfit}");
                $this->line("    Дивиденды: {$dividends}, Выводы: {$dividendsWithdraw}");
                $this->line("    Реинвесты: {$reinvests}, Выводы реинвестов: {$reinvestsWithdraw}");
            }
        }

        $this->info("Общая неправильная прибыль от архивных пакетов: {$totalIncorrectProfit}");
        $this->info("Затронуто пользователей: " . count($userStats));

        foreach ($userStats as $userId => $amount) {
            $user = User::find($userId);
            $username = $user ? $user->username : 'Unknown';
            $this->line("  - {$username} (ID: {$userId}): {$amount}");
        }
    }

    private function analyzeRegularPremiumAccruals(?int $onlyUser): void
    {
        $this->info("\n=== АНАЛИЗ НАЧИСЛЕНИЙ РЕГУЛЯРНОЙ ПРЕМИИ ===");

        $query = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to]);

        if ($onlyUser) {
            $query->where('user_id', $onlyUser);
        }

        $transactions = $query->with('user')->get();

        $this->info("Найдено начислений: {$transactions->count()}");

        $totalAmount = 0;
        $userStats = [];

        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->amount;
            $userStats[$transaction->user_id] = ($userStats[$transaction->user_id] ?? 0) + $transaction->amount;

            $this->line("  - {$transaction->user->username} (ID: {$transaction->user_id}): {$transaction->amount} (UUID: {$transaction->uuid})");
        }

        $this->info("Общая сумма начислений: {$totalAmount}");

        foreach ($userStats as $userId => $amount) {
            $user = User::find($userId);
            $username = $user ? $user->username : 'Unknown';
            $this->line("  - {$username} (ID: {$userId}): {$amount}");
        }
    }

    private function compareWithCorrectCalculation(?int $onlyUser): void
    {
        $this->info("\n=== СРАВНЕНИЕ С ПРАВИЛЬНЫМ РАСЧЕТОМ ===");

        // Правильный расчет (без архивных пакетов)
        $correctPackages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE) // Исключаем архивные
            ->where(function ($q) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]));
            });

        if ($onlyUser) {
            $correctPackages->where('transactions.user_id', $onlyUser);
        }

        $correctPackages = $correctPackages->with([
            'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
            'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
            'withdrawProfitsTransactions' => fn ($p) => $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
            'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
        ])->get();

        $correctNet = [];
        foreach ($correctPackages as $pkg) {
            $user_id = $pkg->user_id;
            $dividends = $pkg->profits->sum('amount');
            $dividendsWithdraw = $pkg->withdrawProfitsTransactions->sum('amount');
            $reinvests = $pkg->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $pkg->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $pkg->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $correctNet[$user_id] = ($correctNet[$user_id] ?? 0)
                + ($dividends - $dividendsWithdraw)
                + ($reinvests - $reinvestsWithdraw);
        }

        $this->info("Правильная чистая прибыль (без архивных пакетов):");
        foreach ($correctNet as $userId => $amount) {
            $user = User::find($userId);
            $username = $user ? $user->username : 'Unknown';
            $this->line("  - {$username} (ID: {$userId}): {$amount}");
        }
    }
}
