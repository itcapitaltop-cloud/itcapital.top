<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingTransactionAccrual;
use App\Models\User;
use App\Settings\GeneralSetting;
use Illuminate\Support\Facades\DB;

final class StakingAccrualService
{
    /**
     * @throws \Throwable
     */
    public function accrueAdminProfit(
        ItcPackage $package,
        float $amount,
        int $userId,
        ?int $sourceUser = null
    ): StakingTransactionAccrual {
        $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
        $token = $amount * $exchangeRateItc;
        $profit = $token - $amount;

        return DB::transaction(function () use ($package, $amount, $profit, $userId, $sourceUser) {
            activity('packages')
                ->performedOn($package)
                ->causedBy(User::query()->findOrFail($userId))
                ->withProperties([
                    'amount' => $amount,
                    'profit' => $profit,
                    'package_uuid' => $package->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('admin_package_purchased');

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::Profit,
                $profit,
                $userId,
                $sourceUser,
            );
        });
    }

    private function accrue(
        ItcPackage $package,
        StakingTransactionAccrualEnum $type,
        float $amount,
        int $userId,
        ?int $sourceUser = null,
    ): StakingTransactionAccrual {
        return $package->stakingTransactionAccruals()->create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'source_user_id' => $sourceUser,
        ]);
    }
}
