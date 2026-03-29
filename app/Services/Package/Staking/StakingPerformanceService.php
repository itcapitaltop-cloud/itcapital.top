<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\ItcPackage;
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
     *     total_profit_usd: float,
     *     average_purchase_rate: float
     * }
     */
    public function forPackage(ItcPackage $package): array
    {
        $package->loadMissing(['transaction', 'stakingPurchases', 'stakingTransactionAccruals']);

        $purchases = $this->normalizePurchases($package->stakingPurchases);
        $topUpBonuses = $package->stakingTransactionAccruals
            ->where('type', StakingTransactionAccrualEnum::TopUpBonus);
        $yieldAccruals = $package->stakingTransactionAccruals
            ->where('type', '!=', StakingTransactionAccrualEnum::TopUpBonus);

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
            $unrealizedPnlUsd = round((float) $purchases->sum(
                fn (array $purchase): float => $purchase['token_amount'] * ($currentRate - $purchase['purchase_rate'])
            ), 2);
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
            'total_profit_usd' => round($currentValueUsd - $investedUsd, 2),
            'average_purchase_rate' => $purchasedTokens > 0
                ? round($investedUsd / $purchasedTokens, 6)
                : 0.0,
        ];
    }

    /**
     * @param Collection<int, StakingPurchase> $purchases
     * @return Collection<int, array{amount_usd: float, token_amount: float, purchase_rate: float}>
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
                ];
            }

            return [
                'amount_usd' => $amountUsd,
                'token_amount' => $tokenAmount,
                'purchase_rate' => $purchaseRate,
            ];
        });
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
     *     total_profit_usd: float,
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
            'total_profit_usd' => round((float) $summary->sum('total_profit_usd'), 2),
            'average_purchase_rate' => $purchasedTokens > 0
                ? round($investedUsd / $purchasedTokens, 6)
                : 0.0,
        ];
    }
}
