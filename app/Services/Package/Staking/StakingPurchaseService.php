<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Models\ItcPackage;
use App\Models\StakingPurchase;
use App\Services\Token\TokenRateResolver;
use Illuminate\Support\Facades\DB;

final class StakingPurchaseService
{
    public function __construct(
        private readonly TokenRateResolver $tokenRateResolver,
        private readonly StakingAccrualService $stakingAccrualService,
    ) {}

    public function createPackage(int $userId, float $amountUsd, float $monthProfitPercent = 2.0): ItcPackage
    {
        return DB::transaction(function () use ($userId, $amountUsd, $monthProfitPercent): ItcPackage {
            $package = CreateStakingPackageAction::make()->run($userId, $amountUsd, $monthProfitPercent);

            $this->recordPurchase($package, $amountUsd, $userId);

            return $package->refresh();
        });
    }

    public function addPurchase(ItcPackage $package, float $amountUsd, int $userId): StakingPurchase
    {
        return DB::transaction(function () use ($package, $amountUsd, $userId): StakingPurchase {
            $package->transaction()->increment('amount', $amountUsd);

            return $this->recordPurchase($package->fresh(['transaction']), $amountUsd, $userId);
        });
    }

    public function recordPurchase(ItcPackage $package, float $amountUsd, int $userId): StakingPurchase
    {
        $purchaseRate = $this->tokenRateResolver->currentRate();
        $tokenAmount = round($amountUsd / $purchaseRate, 2);

        $purchase = $package->stakingPurchases()->create([
            'user_id' => $userId,
            'amount_usd' => round($amountUsd, 2),
            'token_amount' => $tokenAmount,
            'purchase_rate' => round($purchaseRate, 6),
            'purchased_at' => now(),
        ]);

        $this->stakingAccrualService->accrueTopUpStaking($package, $amountUsd, $userId);

        return $purchase;
    }
}
