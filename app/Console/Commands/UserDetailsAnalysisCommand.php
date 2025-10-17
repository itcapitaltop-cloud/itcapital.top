<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerClosure, PartnerReward, Transaction, User, Withdraw
};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UserDetailsAnalysisCommand extends Command
{
    protected $signature = 'regular-premium:user-details
                            {user-ids* : ID пользователей для анализа (через пробел)}
                            {--from= : Дата начала периода (YYYY-MM-DD)}
                            {--to= : Дата окончания периода (YYYY-MM-DD)}
                            {--check-balances : Проверить балансы пользователей}
                            {--check-withdrawals : Проверить заявки на вывод}
                            {--check-packages : Проверить пакеты пользователей}';

    protected $description = 'Детальный анализ конкретных пользователей по ID';

    private Carbon $from;
    private Carbon $to;

    public function handle(): int
    {
        $userIds = $this->argument('user-ids');
        $this->from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->subDays(14)->startOfDay();

        $this->to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : now()->endOfDay();

        $checkBalances = $this->option('check-balances');
        $checkWithdrawals = $this->option('check-withdrawals');
        $checkPackages = $this->option('check-packages');

        $this->info("=== ДЕТАЛЬНЫЙ АНАЛИЗ ПОЛЬЗОВАТЕЛЕЙ ===");
        $this->info("Период: {$this->from->format('Y-m-d H:i:s')} - {$this->to->format('Y-m-d H:i:s')}");
        $this->info("Анализируемые ID: " . implode(', ', $userIds));

        foreach ($userIds as $userId) {
            $this->analyzeUser(intval($userId), $checkBalances, $checkWithdrawals, $checkPackages);
        }

        return self::SUCCESS;
    }

    private function analyzeUser(int $userId, bool $checkBalances, bool $checkWithdrawals, bool $checkPackages): void
    {
        $this->info("\n" . str_repeat("=", 80));
        $this->info("АНАЛИЗ ПОЛЬЗОВАТЕЛЯ ID: {$userId}");
        $this->info(str_repeat("=", 80));

        $user = User::find($userId);

        if (!$user) {
            $this->error("❌ Пользователь с ID {$userId} НЕ НАЙДЕН в базе данных!");
            return;
        }

        $this->info("✅ Пользователь найден:");
        $this->info("  - Username: {$user->username}");
        $this->info("  - Email: {$user->email}");
        $this->info("  - Ранг: {$user->rank}");
        $this->info("  - Статус: " . ($user->banned_at ? "ЗАБЛОКИРОВАН ({$user->banned_at})" : "АКТИВЕН"));
        $this->info("  - Дата регистрации: {$user->created_at->format('Y-m-d H:i:s')}");

        // 1. Проверка участия в регулярной премии
        $this->checkRegularPremiumParticipation($user);

        // 2. Проверка балансов
        if ($checkBalances) {
            $this->checkUserBalances($user);
        }

        // 3. Проверка заявок на вывод
        if ($checkWithdrawals) {
            $this->checkUserWithdrawals($user);
        }

        // 4. Проверка пакетов
        if ($checkPackages) {
            $this->checkUserPackages($user);
        }

        // 5. Проверка партнерской структуры
        $this->checkPartnerStructure($user);

        // 6. Проверка архивных пакетов
        $this->checkArchivedPackages($user);
    }

    private function checkRegularPremiumParticipation(User $user): void
    {
        $this->info("\n--- УЧАСТИЕ В РЕГУЛЯРНОЙ ПРЕМИИ ---");

        // Получал ли регулярную премию
        $receivedPremium = Transaction::where('user_id', $user->id)
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->sum('amount');

        if ($receivedPremium > 0) {
            $this->info("✅ Получал регулярную премию: {$receivedPremium} ITC");

            $transactions = Transaction::where('user_id', $user->id)
                ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->get();

            foreach ($transactions as $trx) {
                $this->line("  - {$trx->accepted_at->format('Y-m-d H:i:s')}: {$trx->amount} ITC (UUID: {$trx->uuid})");
            }
        } else {
            $this->info("❌ НЕ получал регулярную премию в указанный период");
        }

        // Участвовал ли в расчетах как потомок
        $asDescendant = PartnerClosure::where('descendant_id', $user->id)
            ->whereBetween('depth', [1, 8])
            ->exists();

        if ($asDescendant) {
            $this->info("✅ Участвует в расчетах как потомок");

            $ancestors = PartnerClosure::where('descendant_id', $user->id)
                ->whereBetween('depth', [1, 8])
                ->get();

            foreach ($ancestors as $closure) {
                $ancestor = User::find($closure->ancestor_id);
                $ancestorName = $ancestor ? $ancestor->username : 'Unknown';
                $this->line("  - Потомок у пользователя {$ancestorName} (ID: {$closure->ancestor_id}) на линии {$closure->depth}");
            }
        } else {
            $this->info("❌ НЕ участвует в расчетах как потомок");
        }

        // Участвовал ли в расчетах как предок
        $asAncestor = PartnerClosure::where('ancestor_id', $user->id)
            ->whereBetween('depth', [1, 8])
            ->exists();

        if ($asAncestor) {
            $this->info("✅ Участвует в расчетах как предок (ранг: {$user->rank})");

            $descendants = PartnerClosure::where('ancestor_id', $user->id)
                ->whereBetween('depth', [1, 8])
                ->get();

            $this->line("  - Количество потомков: {$descendants->count()}");
        } else {
            $this->info("❌ НЕ участвует в расчетах как предок");
        }
    }

    private function checkUserBalances(User $user): void
    {
        $this->info("\n--- БАЛАНСЫ ПОЛЬЗОВАТЕЛЯ ---");

        // Основной баланс
        $mainBalance = Transaction::where('user_id', $user->id)
            ->where('balance_type', BalanceTypeEnum::MAIN)
            ->sum(DB::raw('CASE WHEN trx_type IN (\'DEPOSIT\', \'WITHDRAW_PACKAGE\', \'REGULAR_PREMIUM_TO_MAIN\') THEN amount ELSE -amount END'));

        $this->info("Основной баланс: {$mainBalance} ITC");

        // Партнерский баланс
        $partnerBalance = Transaction::where('user_id', $user->id)
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->sum(DB::raw('CASE WHEN trx_type IN (\'REGULAR_PREMIUM_ACCRUAL\', \'REGULAR_PREMIUM_TO_PARTNER\') THEN amount ELSE -amount END'));

        $this->info("Партнерский баланс: {$partnerBalance} ITC");

        // Последние транзакции
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('accepted_at', [$this->from, $this->to])
            ->orderBy('accepted_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentTransactions->count() > 0) {
            $this->info("Последние транзакции:");
            foreach ($recentTransactions as $trx) {
                $sign = in_array($trx->trx_type->value, ['DEPOSIT', 'REGULAR_PREMIUM_ACCRUAL', 'WITHDRAW_PACKAGE']) ? '+' : '-';
                $this->line("  - {$trx->accepted_at->format('Y-m-d H:i:s')}: {$sign}{$trx->amount} ITC ({$trx->trx_type->value})");
            }
        }
    }

    private function checkUserWithdrawals(User $user): void
    {
        $this->info("\n--- ЗАЯВКИ НА ВЫВОД ---");

        // Активные заявки
        $activeWithdrawals = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
            ->whereNull('transactions.accepted_at')
            ->whereNull('transactions.rejected_at')
            ->get();

        if ($activeWithdrawals->count() > 0) {
            $this->info("Активные заявки на вывод:");
            foreach ($activeWithdrawals as $withdrawal) {
                $this->line("  - {$withdrawal->amount} ITC (создана: {$withdrawal->created_at->format('Y-m-d H:i:s')})");
            }
        } else {
            $this->info("❌ Нет активных заявок на вывод");
        }

        // Отмененные заявки за период
        $cancelledWithdrawals = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
            ->whereNotNull('transactions.rejected_at')
            ->whereBetween('transactions.rejected_at', [$this->from, $this->to])
            ->get();

        if ($cancelledWithdrawals->count() > 0) {
            $this->info("Отмененные заявки за период:");
            foreach ($cancelledWithdrawals as $withdrawal) {
                $this->line("  - {$withdrawal->amount} ITC (отменена: {$withdrawal->rejected_at->format('Y-m-d H:i:s')})");
            }
        } else {
            $this->info("❌ Нет отмененных заявок за период");
        }

        // Принятые заявки за период
        $acceptedWithdrawals = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
            ->whereNotNull('transactions.accepted_at')
            ->whereBetween('transactions.accepted_at', [$this->from, $this->to])
            ->get();

        if ($acceptedWithdrawals->count() > 0) {
            $this->info("Принятые заявки за период:");
            foreach ($acceptedWithdrawals as $withdrawal) {
                $this->line("  - {$withdrawal->amount} ITC (принята: {$withdrawal->accepted_at->format('Y-m-d H:i:s')})");
            }
        } else {
            $this->info("❌ Нет принятых заявок за период");
        }
    }

    private function checkUserPackages(User $user): void
    {
        $this->info("\n--- ПАКЕТЫ ПОЛЬЗОВАТЕЛЯ ---");

        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
            ->with('transaction')
            ->get();

        if ($packages->count() > 0) {
            $this->info("Всего пакетов: {$packages->count()}");

            $activePackages = $packages->where('type', '!=', PackageTypeEnum::ARCHIVE)->count();
            $archivedPackages = $packages->where('type', PackageTypeEnum::ARCHIVE)->count();

            $this->info("  - Активных: {$activePackages}");
            $this->info("  - Архивных: {$archivedPackages}");

            foreach ($packages as $package) {
                $status = $package->type === PackageTypeEnum::ARCHIVE ? 'АРХИВНЫЙ' : 'АКТИВНЫЙ';
                $this->line("  - {$package->uuid}: {$package->transaction->amount} ITC ({$status})");
            }
        } else {
            $this->info("❌ У пользователя нет пакетов");
        }
    }

    private function checkPartnerStructure(User $user): void
    {
        $this->info("\n--- ПАРТНЕРСКАЯ СТРУКТУРА ---");

        // Прямые потомки
        $directDescendants = PartnerClosure::where('ancestor_id', $user->id)
            ->where('depth', 1)
            ->get();

        if ($directDescendants->count() > 0) {
            $this->info("Прямые потомки ({$directDescendants->count()}):");
            foreach ($directDescendants as $closure) {
                $descendant = User::find($closure->descendant_id);
                $descendantName = $descendant ? $descendant->username : 'Unknown';
                $descendantRank = $descendant ? $descendant->rank : 'Unknown';
                $this->line("  - {$descendantName} (ID: {$closure->descendant_id}, Ранг: {$descendantRank})");
            }
        } else {
            $this->info("❌ Нет прямых потомков");
        }

        // Прямой предок
        $directAncestor = PartnerClosure::where('descendant_id', $user->id)
            ->where('depth', 1)
            ->first();

        if ($directAncestor) {
            $ancestor = User::find($directAncestor->ancestor_id);
            $ancestorName = $ancestor ? $ancestor->username : 'Unknown';
            $ancestorRank = $ancestor ? $ancestor->rank : 'Unknown';
            $this->info("Прямой предок: {$ancestorName} (ID: {$directAncestor->ancestor_id}, Ранг: {$ancestorRank})");
        } else {
            $this->info("❌ Нет прямого предка");
        }
    }

    private function checkArchivedPackages(User $user): void
    {
        $this->info("\n--- АРХИВНЫЕ ПАКЕТЫ ---");

        $archivedPackages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
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
            })
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
                'withdrawProfitsTransactions' => fn ($q) => $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
                'reinvestProfitWithdraws' => fn ($q) => $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
            ])
            ->get();

        if ($archivedPackages->count() > 0) {
            $this->info("Архивные пакеты с активностью: {$archivedPackages->count()}");

            foreach ($archivedPackages as $package) {
                $dividends = $package->profits->sum('amount');
                $dividendsWithdraw = $package->withdrawProfitsTransactions->sum('amount');
                $reinvests = $package->reinvestProfitsAll->sum('amount');
                $withdrawUuids = $package->reinvestProfitWithdraws->pluck('reinvest_uuid');
                $reinvestsWithdraw = $package->reinvestProfitsAll
                    ->whereIn('uuid', $withdrawUuids)
                    ->sum('amount');

                $netProfit = ($dividends - $dividendsWithdraw) + ($reinvests - $reinvestsWithdraw);

                $this->line("  - {$package->uuid}: {$netProfit} ITC чистой прибыли");
                $this->line("    Дивиденды: {$dividends}, Выводы: {$dividendsWithdraw}");
                $this->line("    Реинвесты: {$reinvests}, Выводы реинвестов: {$reinvestsWithdraw}");
            }
        } else {
            $this->info("❌ Нет архивных пакетов с активностью за период");
        }
    }
}
