<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\ItcPackage;
use App\Models\StakingPurchase;
use App\Services\ActivityLog\BusinessActivityLogger;
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

            $this->recordPurchase($package, $amountUsd, $userId, ActivityEventTypeEnum::StakingPackagePurchased);

            return $package->refresh();
        });
    }

    public function addPurchase(ItcPackage $package, float $amountUsd, int $userId): StakingPurchase
    {
        return DB::transaction(function () use ($package, $amountUsd, $userId): StakingPurchase {
            $package->transaction()->increment('amount', $amountUsd);

            return $this->recordPurchase(
                $package->fresh(['transaction']),
                $amountUsd,
                $userId,
                ActivityEventTypeEnum::StakingPackageToppedUp,
            );
        });
    }

    public function addPurchaseByTokens(ItcPackage $package, float $tokenAmount, int $userId): StakingPurchase
    {
        return DB::transaction(function () use ($package, $tokenAmount, $userId): StakingPurchase {
            $purchaseRate = $this->tokenRateResolver->currentRate();
            $amountUsd = round($tokenAmount * $purchaseRate, 2);

            $package->transaction()->increment('amount', $amountUsd);

            $purchase = $package->stakingPurchases()->create([
                'user_id' => $userId,
                'amount_usd' => $amountUsd,
                'token_amount' => round($tokenAmount, 2),
                'purchase_rate' => round($purchaseRate, 6),
                'purchased_at' => now(),
            ]);

            $this->stakingAccrualService->accrueTopUpStaking($package, $amountUsd, $userId, false);

            return $purchase;
        });
    }

    public function recordPurchase(
        ItcPackage $package,
        float $amountUsd,
        int $userId,
        ?ActivityEventTypeEnum $activityType = null,
    ): StakingPurchase {
        $purchaseRate = $this->tokenRateResolver->currentRate();
        $tokenAmount = round($amountUsd / $purchaseRate, 2);

        $purchase = $package->stakingPurchases()->create([
            'user_id' => $userId,
            'amount_usd' => round($amountUsd, 2),
            'token_amount' => $tokenAmount,
            'purchase_rate' => round($purchaseRate, 6),
            'purchased_at' => now(),
        ]);

        if ($activityType !== null) {
            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: $activityType,
                userId: $userId,
                subject: $package,
                feeds: [ActivityFeedTypeEnum::Staking, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => (string) round($amountUsd, 2),
                    'package_uuid' => $package->uuid,
                    'package_type' => $package->type->value,
                    'token_amount' => $tokenAmount,
                    'purchase_rate' => round($purchaseRate, 6),
                ],
                causer: auth()->user(),
                logName: 'packages',
                context: auth()->check() ? 'account' : 'system',
            ));
        }

        $this->stakingAccrualService->accrueTopUpStaking($package, $amountUsd, $userId, false);

        return $purchase;
    }
}
