<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingPerformanceService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SummaryMetricsService
{
    /**
     * Cache key for the fully computed admin dashboard snapshot.
     */
    public const string CACHE_KEY = 'admin.summary.dashboard';

    /**
     * How long a computed snapshot stays warm. The dashboard does not need
     * second-level accuracy, so we trade freshness for a near-instant page.
     */
    public const int CACHE_TTL_SECONDS = 600;

    /**
     * Compute (or read from cache) every dashboard metric in a single pass.
     *
     * All counts and sums exclude test users (`is_test = true`) and banned
     * users (filtered globally via the `notBanned` scope on every `user`
     * relationship chain), so the snapshot is the single audited source for
     * the admin summary page.
     *
     * @return array{
     *     users: array{total: int, week: int, today: int},
     *     deposits: array{total_count: int, total_sum: float, week_count: int, week_sum: float, month_count: int, month_sum: float},
     *     withdraws: array{total_count: int, total_sum: float, week_count: int, week_sum: float, month_count: int, month_sum: float},
     *     packages: array<string, float>,
     *     balances: array{main: float, package_dividends: float, partner: float, regular_premium: float, token: float},
     *     accruals: array{dividends_month: float, dividends_week: float, start_bonus_month: float, start_bonus_week: float, regular_premium_month: float, regular_premium_week: float, rank_bonus_month: float, rank_bonus_week: float, staking_profits_month: float, staking_profits_week: float}
     * }
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => $this->compute());
    }

    /**
     * Force a fresh computation and refill the cache. Used by scheduled warming.
     *
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        $snapshot = $this->compute();

        Cache::put(self::CACHE_KEY, $snapshot, self::CACHE_TTL_SECONDS);

        return $snapshot;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(): array
    {
        $packages = $this->totalPackagesAmount();

        return [
            'users' => [
                'total' => $this->usersTotal(),
                'week' => $this->usersNewThisWeek(),
                'today' => $this->usersNewToday(),
            ],
            'deposits' => [
                'total_count' => $this->depositCount(),
                'total_sum' => $this->depositSum(),
                'week_count' => $this->depositCount(now()->startOfWeek()),
                'week_sum' => $this->depositSum(now()->startOfWeek()),
                'month_count' => $this->depositCount(now()->startOfMonth()),
                'month_sum' => $this->depositSum(now()->startOfMonth()),
            ],
            'withdraws' => [
                'total_count' => $this->withdrawCount(),
                'total_sum' => $this->withdrawSum(),
                'week_count' => $this->withdrawCount(now()->startOfWeek()),
                'week_sum' => $this->withdrawSum(now()->startOfWeek()),
                'month_count' => $this->withdrawCount(now()->startOfMonth()),
                'month_sum' => $this->withdrawSum(now()->startOfMonth()),
            ],
            'packages' => $packages,
            'balances' => [
                'main' => (float) $this->mainBalance(),
                'package_dividends' => (float) $this->packageDividends(),
                'partner' => (float) $this->partnerBalance(),
                'regular_premium' => $this->regularPremiumNet(),
                // Reuse the staking total already computed above instead of
                // recomputing the full staking performance a second time.
                'token' => (float) ($packages[PackageTypeEnum::STAKING->value] ?? 0),
            ],
            'accruals' => [
                'dividends_month' => (float) $this->dividendsMonth(),
                'dividends_week' => (float) $this->dividendsWeek(),
                'start_bonus_month' => (float) $this->startBonusMonth(),
                'start_bonus_week' => (float) $this->startBonusWeek(),
                'regular_premium_month' => (float) $this->regularPremiumMonth(),
                'regular_premium_week' => (float) $this->regularPremiumWeek(),
                'rank_bonus_month' => (float) $this->rankBonusMonth(),
                'rank_bonus_week' => (float) $this->rankBonusWeek(),
                'staking_profits_month' => (float) $this->stakingProfitsMonth(),
                'staking_profits_week' => (float) $this->stakingProfitsWeek(),
            ],
        ];
    }

    public function usersTotal(): int
    {
        return User::query()->where('is_test', false)->count();
    }

    public function usersNewThisWeek(): int
    {
        return User::query()
            ->where('is_test', false)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
    }

    public function usersNewToday(): int
    {
        return User::query()
            ->where('is_test', false)
            ->whereDate('created_at', today())
            ->count();
    }

    public function depositCount(?CarbonInterface $since = null): int
    {
        return Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->when($since !== null, fn ($q) => $q->where('accepted_at', '>=', $since))
            ->count();
    }

    public function depositSum(?CarbonInterface $since = null): float
    {
        return round((float) Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->when($since !== null, fn ($q) => $q->where('accepted_at', '>=', $since))
            ->sum('amount'), 2);
    }

    public function withdrawCount(?CarbonInterface $since = null): int
    {
        return Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->count();
    }

    public function withdrawSum(?CarbonInterface $since = null): float
    {
        return round((float) Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->sum('amount'), 2);
    }

    /**
     * Net regular-premium balance (debits minus credits), excluding test and
     * banned users. Replaces the unfiltered TransactionRepository::getRegularBonus.
     */
    public function regularPremiumNet(): float
    {
        $debit = (float) Transaction::query()
            ->withoutTestUsers()
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getDebits())
            ->sum('amount');

        $credit = (float) Transaction::query()
            ->withoutTestUsers()
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getCredits())
            ->sum('amount');

        return round($debit - $credit, 2);
    }

    public function totalPackagesAmount(): array
    {
        $totals = ItcPackage::query()
            ->withoutTestUsers()
            ->whereNotIn('type', [PackageTypeEnum::ARCHIVE])
            ->select('type')
            ->withSum(['transaction as deposit_sum'], 'amount')
            ->withSum('partnerTransfers', 'amount')
            ->withSum(
                ['stakingTransactionAccruals as staking_transaction_accruals_sum_amount' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0) / 100'))],
                'amount'
            )
            ->withSum('reinvestToBody', 'amount')
            ->withSum('balanceWithdraws', 'amount')
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => round(
                $items->sum(fn ($p) => $p->deposit_sum
                    + $p->partner_transfers_sum_amount
                    + $p->reinvest_to_body_sum_amount
                    + $p->staking_transaction_accruals_sum_amount
                    - $p->balance_withdraws_sum_amount
                ),
                2
            ))
            ->toArray();

        $stakingPackages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->withoutTestUsers()
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
            ->get();

        if ($stakingPackages->isNotEmpty()) {
            $totals[PackageTypeEnum::STAKING->value] = app(StakingPerformanceService::class)->forPackages($stakingPackages)['total_tokens'];
        }

        return $totals;
    }

    public function mainBalance(): float
    {
        return (float) Transaction::query()
            ->withoutTestUsers()
            ->where('balance_type', BalanceTypeEnum::MAIN)
            ->whereNotNull('accepted_at')
            ->sum(DB::raw("
        CASE
            WHEN trx_type IN ('" . implode("','", array_map(fn ($e) => $e->value, TrxTypeEnum::getDebits())) . "')
            THEN amount
            ELSE -amount
        END
    "));
    }

    public function packageDividends(): float
    {
        return (float) PackageProfit::query()
            ->withoutTestUsers()
            ->whereDoesntHave('reinvestLink')
            ->whereDoesntHave('withdraw')
            ->sum('amount');
    }

    public function partnerBalance(): float
    {
        return (float) Transaction::query()
            ->withoutTestUsers()
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereNotNull('accepted_at')
            ->whereIn('trx_type', [
                TrxTypeEnum::PARTNER_TRANSFER_IN,
                TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER,
                TrxTypeEnum::START_BONUS_ACCRUAL,
                TrxTypeEnum::RANK_BONUS_ACCRUAL,

                TrxTypeEnum::PARTNER_TRANSFER_OUT,
                TrxTypeEnum::PARTNER_TO_MAIN_SELF,
                TrxTypeEnum::PARTNER_TO_PACKAGE,
                TrxTypeEnum::PARTNER_BONUS_ROLLBACK,
            ])
            ->sum(DB::raw("
            CASE
                WHEN trx_type IN (
                    'partner_transfer_in',
                    'regular_premium_to_partner',
                    'start_bonus_accrual',
                    'rank_bonus_accrual'
                )
                THEN amount
                ELSE -amount
            END
        "));
    }

    public function regularPremiumBalance(): float|int
    {
        return Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->whereNotNull('accepted_at')
            ->sum('amount')
    -
    Transaction::query()
        ->withoutTestUsers()
        ->whereIn('trx_type', [
            TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER,
            TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER_MIRROR,
        ])
        ->sum('amount');
    }

    public function sumByPeriod(TrxTypeEnum $type, string $period): int|float|string
    {
        return Transaction::query()
            ->withoutTestUsers()
            ->where('trx_type', $type)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '>=', now()->sub($period))
            ->sum('amount');
    }

    public function dividendsMonth(): int|float|string
    {
        return PackageProfit::query()->withoutTestUsers()->where('created_at', '>=', now()->subMonth())->sum('amount');
    }

    public function dividendsWeek(): int|float|string
    {
        return PackageProfit::query()->withoutTestUsers()->where('created_at', '>=', now()->subWeek())->sum('amount');
    }

    public function startBonusMonth(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::START_BONUS_ACCRUAL, '1 month');
    }

    public function startBonusWeek(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::START_BONUS_ACCRUAL, '1 week');
    }

    public function regularPremiumMonth(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL, '1 month');
    }

    public function regularPremiumWeek(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL, '1 week');
    }

    public function rankBonusMonth(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::RANK_BONUS_ACCRUAL, '1 month');
    }

    public function rankBonusWeek(): int|float|string
    {
        return $this->sumByPeriod(TrxTypeEnum::RANK_BONUS_ACCRUAL, '1 week');
    }

    public function stakingProfitsMonth(): int|float|string
    {
        return PackageProfit::query()
            ->withoutTestUsers()
            ->whereHas('package', fn ($q) => $q->where('type', PackageTypeEnum::STAKING)
            )
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount');
    }

    public function stakingProfitsWeek(): int|float|string
    {
        return PackageProfit::query()
            ->withoutTestUsers()
            ->whereHas('package', fn ($q) => $q->where('type', PackageTypeEnum::STAKING)
            )
            ->where('created_at', '>=', now()->subWeek())
            ->sum('amount');
    }
}
