<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Services\Package\Staking\StakingPerformanceService;
use Illuminate\Support\Facades\DB;

final class SummaryMetricsService
{
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

    public function mainBalance(): string
    {
        return Transaction::query()
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

    public function packageDividends(): string
    {
        return PackageProfit::query()
            ->withoutTestUsers()
            ->whereDoesntHave('reinvestLink')
            ->whereDoesntHave('withdraw')
            ->sum('amount');
    }

    public function partnerBalance(): string
    {
        return Transaction::query()
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

    public function tokenBalance(): int|float
    {
        $packages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->withoutTestUsers()
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
            ->get();

        return app(StakingPerformanceService::class)->forPackages($packages)['total_tokens'];
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
