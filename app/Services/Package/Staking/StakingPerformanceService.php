<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingTransactionAccrual;
use App\Models\StakingPurchase;
use App\Services\Token\TokenRateResolver;
use Illuminate\Support\Collection;

final class StakingPerformanceService
{
    public function __construct(
        private readonly TokenRateResolver $tokenRateResolver,
    ) {}

    /**
     * @return array{
     *     current_rate: float,
     *     invested_usd: float,
     *     purchased_tokens: float,
     *     yield_tokens: float,
     *     total_tokens: float,
     *     current_value_usd: float,
     *     purchase_value_usd: float,
     *     unrealized_pnl_usd: float,
     *     average_purchase_rate: float
     * }
     */
    public function forPackage(ItcPackage $package): array
    {
        $package->loadMissing(['transaction', 'stakingPurchases', 'stakingTransactionAccruals']);

        $purchases = $this->resolvePurchases($package);
        $topUpBonuses = $package->stakingTransactionAccruals
            ->where('type', StakingTransactionAccrualEnum::TopUpBonus);
        $yieldAccruals = $package->stakingTransactionAccruals
            ->where('type', '!=', StakingTransactionAccrualEnum::TopUpBonus);
        $accrualLots = $this->normalizeAccruals($yieldAccruals);

        $investedUsd = $purchases->isNotEmpty()
            ? round((float) $purchases->sum('amount_usd'), 2)
            : round((float) ($package->transaction?->amount ?? 0), 2);

        $purchasedTokens = $purchases->isNotEmpty()
            ? round((float) $purchases->sum('token_amount'), 2)
            : round((float) ($package->transaction?->amount ?? 0) + (float) $topUpBonuses->sum('amount'), 2);

        $yieldTokens = round((float) $yieldAccruals->sum('amount'), 2);
        $totalTokens = round($purchasedTokens + $yieldTokens, 2);
        $currentRate = $this->tokenRateResolver->currentRate();
        $currentValueUsd = round($totalTokens * $currentRate, 2);
        $purchaseValueUsd = round($purchasedTokens * $currentRate, 2);

        if ($purchases->isNotEmpty()) {
            $purchasePnlUsd = (float) $purchases->sum(
                fn (array $purchase): float => $purchase['token_amount'] * ($currentRate - $purchase['purchase_rate'])
            );
            $accrualPnlUsd = (float) $accrualLots->sum(
                fn (array $accrual): float => $accrual['token_amount'] * ($currentRate - $accrual['accrual_rate'])
            );

            $unrealizedPnlUsd = round($purchasePnlUsd + $accrualPnlUsd, 2);
        } else {
            $unrealizedPnlUsd = round($purchaseValueUsd - $investedUsd, 2);
        }

        return [
            'current_rate' => round($currentRate, 6),
            'invested_usd' => $investedUsd,
            'purchased_tokens' => $purchasedTokens,
            'yield_tokens' => $yieldTokens,
            'total_tokens' => $totalTokens,
            'current_value_usd' => $currentValueUsd,
            'purchase_value_usd' => $purchaseValueUsd,
            'unrealized_pnl_usd' => $unrealizedPnlUsd,
            'average_purchase_rate' => $purchasedTokens > 0
                ? round($investedUsd / $purchasedTokens, 6)
                : 0.0,
        ];
    }

    /**
     * @param Collection<int, StakingPurchase> $purchases
     * @return Collection<int, array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null}>
     */
    private function normalizePurchases(Collection $purchases): Collection
    {
        $legacyRate = $this->tokenRateResolver->earliestRate();
        $firstTokenRateDate = $this->tokenRateResolver->earliestEffectiveFrom();

        return $purchases->map(function (StakingPurchase $purchase) use ($legacyRate, $firstTokenRateDate): array {
            $tokenAmount = round((float) $purchase->token_amount, 2);
            $amountUsd = round((float) $purchase->amount_usd, 2);
            $purchaseRate = round((float) $purchase->purchase_rate, 6);

            if ($this->isLegacyBackfilledPurchase($purchase, $legacyRate, $firstTokenRateDate)) {
                return [
                    'amount_usd' => round($tokenAmount * $legacyRate, 2),
                    'token_amount' => $tokenAmount,
                    'purchase_rate' => round($legacyRate, 6),
                    'purchased_at' => $purchase->purchased_at?->timestamp,
                ];
            }

            return [
                'amount_usd' => $amountUsd,
                'token_amount' => $tokenAmount,
                'purchase_rate' => $purchaseRate,
                'purchased_at' => $purchase->purchased_at?->timestamp,
            ];
        });
    }

    /**
     * @param Collection<int, StakingTransactionAccrual> $accruals
     * @return Collection<int, array{token_amount: float, accrual_rate: float}>
     */
    private function normalizeAccruals(Collection $accruals): Collection
    {
        $ratesByDate = [];

        return $accruals
            ->map(function (StakingTransactionAccrual $accrual) use (&$ratesByDate): array {
                $tokenAmount = round((float) $accrual->amount, 2);
                $accrualDate = $accrual->created_at?->toDateString() ?? now()->toDateString();
                $accrualRate = $accrual->accrual_rate !== null
                    ? round((float) $accrual->accrual_rate, 6)
                    : ($ratesByDate[$accrualDate] ??= round(
                        $this->tokenRateResolver->rateForDate($accrual->created_at),
                        6
                    ));

                return [
                    'token_amount' => $tokenAmount,
                    'accrual_rate' => $accrualRate,
                ];
            })
            ->filter(fn (array $accrual): bool => $accrual['token_amount'] > 0)
            ->values();
    }

    /**
     * @return Collection<int, array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null}>
     */
    private function resolvePurchases(ItcPackage $package): Collection
    {
        $purchases = $this->normalizePurchases($package->stakingPurchases);
        $missingInvestedAmount = round((float) ($package->transaction?->amount ?? 0) - (float) $purchases->sum('amount_usd'), 2);

        if ($missingInvestedAmount <= 0.01) {
            return $purchases;
        }

        return $purchases->merge($this->recoverMissingPurchasesFromActivity($package, $purchases, $missingInvestedAmount));
    }

    /**
     * @param Collection<int, array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null}> $purchases
     * @return Collection<int, array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null}>
     */
    private function recoverMissingPurchasesFromActivity(
        ItcPackage $package,
        Collection $purchases,
        float $missingInvestedAmount,
    ): Collection {
        $activityPurchases = BusinessActivity::query()
            ->where('subject_type', ItcPackage::class)
            ->where('subject_id', $package->id)
            ->where('description', 'top_up_package')
            ->orderBy('created_at')
            ->get()
            ->map(function (BusinessActivity $activity): array {
                $tokenAmount = round((float) $activity->getExtraProperty('amount', 0), 2);
                $purchaseRate = round((float) $activity->getExtraProperty('exchange_rate', 0), 6);

                return [
                    'amount_usd' => round($tokenAmount * $purchaseRate, 2),
                    'token_amount' => $tokenAmount,
                    'purchase_rate' => $purchaseRate,
                    'purchased_at' => $activity->created_at?->timestamp,
                ];
            })
            ->filter(fn (array $purchase): bool => $purchase['amount_usd'] > 0 && $purchase['token_amount'] > 0)
            ->values();

        foreach ($purchases as $purchase) {
            $matchedIndex = $activityPurchases->search(
                fn (array $activityPurchase): bool => $this->isSamePurchase($purchase, $activityPurchase)
            );

            if ($matchedIndex !== false) {
                $activityPurchases->forget($matchedIndex);
                $activityPurchases = $activityPurchases->values();
            }
        }

        $recoveredPurchases = collect();

        foreach ($activityPurchases as $activityPurchase) {
            if ($missingInvestedAmount <= 0.01) {
                break;
            }

            if ($activityPurchase['amount_usd'] - $missingInvestedAmount > 0.01) {
                continue;
            }

            $recoveredPurchases->push($activityPurchase);
            $missingInvestedAmount = round($missingInvestedAmount - $activityPurchase['amount_usd'], 2);
        }

        return $recoveredPurchases;
    }

    /**
     * @param array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null} $left
     * @param array{amount_usd: float, token_amount: float, purchase_rate: float, purchased_at: int|null} $right
     */
    private function isSamePurchase(array $left, array $right): bool
    {
        $timestampsMatch = $left['purchased_at'] === null
            || $right['purchased_at'] === null
            || abs($left['purchased_at'] - $right['purchased_at']) <= 60;

        return $timestampsMatch
            && abs($left['amount_usd'] - $right['amount_usd']) <= 0.01
            && abs($left['token_amount'] - $right['token_amount']) <= 0.01
            && abs($left['purchase_rate'] - $right['purchase_rate']) <= 0.000001;
    }

    private function isLegacyBackfilledPurchase(
        StakingPurchase $purchase,
        ?float $legacyRate,
        ?string $firstTokenRateDate,
    ): bool {
        if ($legacyRate === null || $firstTokenRateDate === null || $purchase->purchased_at === null) {
            return false;
        }

        if ($purchase->purchased_at->toDateString() >= $firstTokenRateDate) {
            return false;
        }

        return abs((float) $purchase->purchase_rate - 1.0) < 0.000001
            && abs((float) $purchase->amount_usd - (float) $purchase->token_amount) < 0.01;
    }

    /**
     * @param Collection<int, ItcPackage> $packages
     * @return array{
     *     current_rate: float,
     *     invested_usd: float,
     *     purchased_tokens: float,
     *     yield_tokens: float,
     *     total_tokens: float,
     *     current_value_usd: float,
     *     purchase_value_usd: float,
     *     unrealized_pnl_usd: float,
     *     average_purchase_rate: float
     * }
     */
    public function forPackages(Collection $packages): array
    {
        $summary = $packages->map(fn (ItcPackage $package): array => $this->forPackage($package));

        $investedUsd = round((float) $summary->sum('invested_usd'), 2);
        $purchasedTokens = round((float) $summary->sum('purchased_tokens'), 2);

        return [
            'current_rate' => (float) ($summary->first()['current_rate'] ?? $this->tokenRateResolver->currentRate()),
            'invested_usd' => $investedUsd,
            'purchased_tokens' => $purchasedTokens,
            'yield_tokens' => round((float) $summary->sum('yield_tokens'), 2),
            'total_tokens' => round((float) $summary->sum('total_tokens'), 2),
            'current_value_usd' => round((float) $summary->sum('current_value_usd'), 2),
            'purchase_value_usd' => round((float) $summary->sum('purchase_value_usd'), 2),
            'unrealized_pnl_usd' => round((float) $summary->sum('unrealized_pnl_usd'), 2),
            'average_purchase_rate' => $purchasedTokens > 0
                ? round($investedUsd / $purchasedTokens, 6)
                : 0.0,
        ];
    }
}
