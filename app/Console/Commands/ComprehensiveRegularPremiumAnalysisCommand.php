<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum, TransactionStatusEnum};
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerClosure, PartnerLevelPercent, PartnerReward,
    Transaction, User, Withdraw
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComprehensiveRegularPremiumAnalysisCommand extends Command
{
    protected $signature = 'regular-premium:comprehensive-analysis
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--user= : ID пользователя для анализа}
                            {--include-rank-0 : Включить анализ пользователей с рангом 0}
                            {--check-withdrawals : Проверить отмененные заявки на вывод}
                            {--check-archived-fluctuation : Анализ колебаний архивных пакетов}';

    protected $description = 'Комплексный анализ проблем с регулярной премией: ранг 0, отмененные выводы, архивные пакеты';

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
        $includeRank0 = $this->option('include-rank-0');
        $checkWithdrawals = $this->option('check-withdrawals');
        $checkArchivedFluctuation = $this->option('check-archived-fluctuation');

        $this->info("=== КОМПЛЕКСНЫЙ АНАЛИЗ РЕГУЛЯРНОЙ ПРЕМИИ ===");
        $this->info("Период: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");

        // 1. Анализ пользователей с рангом 0
        if ($includeRank0) {
            $this->analyzeRank0Users($onlyUser);
        }

        // 2. Анализ отмененных заявок на вывод
        if ($checkWithdrawals) {
            $this->analyzeCancelledWithdrawals($onlyUser);
        }

        // 3. Анализ колебаний архивных пакетов
        if ($checkArchivedFluctuation) {
            $this->analyzeArchivedPackageFluctuation($onlyUser);
        }

        // 4. Общий анализ начислений
        $this->analyzeRegularPremiumAccruals($onlyUser);

        // 5. Поиск "потерянных" пользователей
        $this->findMissingUsersInCalculations($onlyUser);

        return self::SUCCESS;
    }

    private function analyzeRank0Users(?int $onlyUser): void
    {
        $this->info("\n=== АНАЛИЗ ПОЛЬЗОВАТЕЛЕЙ С РАНГОМ 0 ===");

        $query = User::where('rank', 0)
            ->whereNull('banned_at');

        if ($onlyUser) {
            $query->where('id', $onlyUser);
        }

        $rank0Users = $query->get();

        $this->info("Найдено пользователей с рангом 0: {$rank0Users->count()}");

        $totalIncorrectAccruals = 0;
        $affectedUsers = [];

        foreach ($rank0Users as $user) {
            // Проверяем, получал ли пользователь регулярную премию
            $accruals = Transaction::where('user_id', $user->id)
                ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->sum('amount');

            if ($accruals > 0) {
                $totalIncorrectAccruals += $accruals;
                $affectedUsers[$user->id] = $accruals;

                $this->warn("  - Пользователь {$user->username} (ID: {$user->id}): получил {$accruals} ITC при ранге 0");
            }
        }

        if ($totalIncorrectAccruals > 0) {
            $this->error("ОБНАРУЖЕНО НЕПРАВИЛЬНЫХ НАЧИСЛЕНИЙ ДЛЯ РАНГА 0: {$totalIncorrectAccruals} ITC");
            $this->error("Затронуто пользователей: " . count($affectedUsers));
        } else {
            $this->info("Пользователи с рангом 0 не получали регулярную премию - это правильно.");
        }
    }

    private function analyzeCancelledWithdrawals(?int $onlyUser): void
    {
        $this->info("\n=== АНАЛИЗ ОТМЕНЕННЫХ ЗАЯВОК НА ВЫВОД ===");

        $query = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->whereNotNull('transactions.rejected_at')
            ->whereBetween('transactions.rejected_at', [$this->from, $this->to])
            ->select([
                'withdraws.uuid',
                'transactions.user_id',
                'transactions.amount',
                'transactions.rejected_at',
                'withdraws.commission'
            ]);

        if ($onlyUser) {
            $query->where('transactions.user_id', $onlyUser);
        }

        $cancelledWithdrawals = $query->with('transaction.user')->get();

        $this->info("Найдено отмененных заявок на вывод: {$cancelledWithdrawals->count()}");

        $totalCancelledAmount = 0;
        $userStats = [];

        foreach ($cancelledWithdrawals as $withdrawal) {
            $totalCancelledAmount += $withdrawal->amount;
            $userStats[$withdrawal->user_id] = ($userStats[$withdrawal->user_id] ?? 0) + $withdrawal->amount;

            $user = $withdrawal->transaction->user ?? User::find($withdrawal->user_id);
            $username = $user ? $user->username : 'Unknown';

            $this->line("  - Пользователь {$username} (ID: {$withdrawal->user_id}): {$withdrawal->amount} ITC");
            $rejectedAt = is_string($withdrawal->rejected_at) ? Carbon::parse($withdrawal->rejected_at) : $withdrawal->rejected_at;
            $this->line("    Отменено: {$rejectedAt->format('Y-m-d H:i:s')}");
        }

        $this->info("Общая сумма отмененных выводов: {$totalCancelledAmount} ITC");
        $this->info("Затронуто пользователей: " . count($userStats));

        // Проверяем, были ли эти пользователи в списке затронутых регулярной премией
        $this->checkWithdrawalUsersInRegularPremium($userStats);
    }

    private function checkWithdrawalUsersInRegularPremium(array $withdrawalUsers): void
    {
        $this->info("\n--- Проверка связи с регулярной премией ---");

        $regularPremiumUsers = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $intersection = array_intersect(array_keys($withdrawalUsers), $regularPremiumUsers);

        if (!empty($intersection)) {
            $this->warn("Пользователи с отмененными выводами, которые также получали регулярную премию:");
            foreach ($intersection as $userId) {
                $user = User::find($userId);
                $username = $user ? $user->username : 'Unknown';
                $this->line("  - {$username} (ID: {$userId})");
            }
        } else {
            $this->info("Нет пересечений между пользователями с отмененными выводами и получателями регулярной премии.");
        }
    }

    private function analyzeArchivedPackageFluctuation(?int $onlyUser): void
    {
        $this->info("\n=== АНАЛИЗ КОЛЕБАНИЙ АРХИВНЫХ ПАКЕТОВ ===");

        // Анализируем несколько недель назад для сравнения
        $previousWeekFrom = $this->from->copy()->subWeek();
        $previousWeekTo = $this->to->copy()->subWeek();

        $this->info("Сравниваем с предыдущей неделей: {$previousWeekFrom->format('Y-m-d')} - {$previousWeekTo->format('Y-m-d')}");

        $currentWeekData = $this->getArchivedPackageData($this->from, $this->to, $onlyUser);
        $previousWeekData = $this->getArchivedPackageData($previousWeekFrom, $previousWeekTo, $onlyUser);

        $this->info("\nТекущая неделя:");
        $this->displayArchivedPackageData($currentWeekData);

        $this->info("\nПредыдущая неделя:");
        $this->displayArchivedPackageData($previousWeekData);

        // Сравнение
        $this->info("\n--- Сравнение недель ---");
        foreach ($currentWeekData as $userId => $currentAmount) {
            $previousAmount = $previousWeekData[$userId] ?? 0;
            $difference = $currentAmount - $previousAmount;

            if (abs($difference) > 0.01) {
                $user = User::find($userId);
                $username = $user ? $user->username : 'Unknown';
                $this->warn("  - {$username} (ID: {$userId}): {$previousAmount} → {$currentAmount} (Δ{$difference})");
            }
        }
    }

    private function getArchivedPackageData(Carbon $from, Carbon $to, ?int $onlyUser): array
    {
        $query = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('itc_packages.type', PackageTypeEnum::ARCHIVE)
            ->where(function ($q) use ($from, $to) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$from, $to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$from, $to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$from, $to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$from, $to]));
            });

        if ($onlyUser) {
            $query->where('transactions.user_id', $onlyUser);
        }

        $archivedPackages = $query->with([
            'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$from, $to]),
            'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$from, $to]),
            'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$from, $to]),
            'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$from, $to]),
        ])->get();

        $userData = [];

        foreach ($archivedPackages as $package) {
            $user_id = $package->user_id;

            $dividends = $package->profits->sum('amount');
            $dividendsWithdraw = $package->withdrawProfitsTransactions->sum('amount');
            $reinvests = $package->reinvestProfitsAll->sum('amount');
            $withdrawUuids = $package->reinvestProfitWithdraws->pluck('reinvest_uuid');
            $reinvestsWithdraw = $package->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $netProfit = ($dividends - $dividendsWithdraw) + ($reinvests - $reinvestsWithdraw);

            if ($netProfit > 0) {
                $userData[$user_id] = ($userData[$user_id] ?? 0) + $netProfit;
            }
        }

        return $userData;
    }

    private function displayArchivedPackageData(array $data): void
    {
        $total = array_sum($data);
        $this->info("Общая прибыль от архивных пакетов: {$total}");
        $this->info("Затронуто пользователей: " . count($data));

        foreach ($data as $userId => $amount) {
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
        $rankStats = [];

        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->amount;
            $userStats[$transaction->user_id] = ($userStats[$transaction->user_id] ?? 0) + $transaction->amount;

            $user = $transaction->user;
            $rank = $user ? $user->rank : 'Unknown';
            $rankStats[$rank] = ($rankStats[$rank] ?? 0) + $transaction->amount;

            $this->line("  - {$user->username} (ID: {$transaction->user_id}, Ранг: {$rank}): {$transaction->amount} (UUID: {$transaction->uuid})");
        }

        $this->info("Общая сумма начислений: {$totalAmount}");
        $this->info("Затронуто пользователей: " . count($userStats));

        $this->info("\n--- Статистика по рангам ---");
        foreach ($rankStats as $rank => $amount) {
            $this->line("  - Ранг {$rank}: {$amount} ITC");
        }
    }

    private function findMissingUsersInCalculations(?int $onlyUser): void
    {
        $this->info("\n=== ПОИСК 'ПОТЕРЯННЫХ' ПОЛЬЗОВАТЕЛЕЙ ===");

        // Получаем всех пользователей, которые участвовали в расчетах
        $calculationUsers = $this->getUsersInCalculations($onlyUser);

        // Получаем всех пользователей, которые получили начисления
        $accrualUsers = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->when($onlyUser, fn($q) => $q->where('user_id', $onlyUser))
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Находим пользователей, которые участвовали в расчетах, но не получили начисления
        $missingInAccruals = array_diff($calculationUsers, $accrualUsers);

        // Находим пользователей, которые получили начисления, но не участвовали в расчетах
        $missingInCalculations = array_diff($accrualUsers, $calculationUsers);

        if (!empty($missingInAccruals)) {
            $this->warn("Пользователи, участвовавшие в расчетах, но не получившие начисления:");
            foreach ($missingInAccruals as $userId) {
                $user = User::find($userId);
                $username = $user ? $user->username : 'Unknown';
                $this->line("  - {$username} (ID: {$userId})");
            }
        }

        if (!empty($missingInCalculations)) {
            $this->warn("Пользователи, получившие начисления, но не участвовавшие в расчетах:");
            foreach ($missingInCalculations as $userId) {
                $user = User::find($userId);
                $username = $user ? $user->username : 'Unknown';
                $this->line("  - {$username} (ID: {$userId})");
            }
        }

        if (empty($missingInAccruals) && empty($missingInCalculations)) {
            $this->info("Все пользователи корректно участвуют в расчетах и начислениях.");
        }
    }

    private function getUsersInCalculations(?int $onlyUser): array
    {
        // Получаем всех пользователей, которые имеют потомков и могли бы получать регулярную премию
        $query = PartnerClosure::whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'));

        if ($onlyUser) {
            $query->where('ancestor_id', $onlyUser);
        }

        return $query->pluck('ancestor_id')->unique()->toArray();
    }
}
