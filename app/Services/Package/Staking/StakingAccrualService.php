<?php

declare(strict_types=1);

namespace App\Services\Package\Staking;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingTransactionAccrual;
use App\Models\User;
use App\Services\Token\TokenRateResolver;
use Illuminate\Support\Facades\DB;

final class StakingAccrualService
{
    /**
     * @throws \Throwable
     */
    public function accrueAdminTopUpBonus(
        ItcPackage $package,
        float $amount,
        int $userId,
    ): StakingTransactionAccrual {
        $token = round($amount / app(TokenRateResolver::class)->currentRate(), 2);
        $profit = $token - $amount;

        return DB::transaction(function () use ($package, $amount, $profit, $userId) {
            activity('packages')
                ->performedOn($package)
                ->causedBy(User::query()->findOrFail($userId))
                ->withProperties([
                    'amount' => $amount,
                    'profit' => $profit,
                    'package_uuid' => $package->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'exchange_rate' => app(TokenRateResolver::class)->currentRate(),
                ])
                ->log('admin_package_purchased');

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::TopUpBonus,
                $profit,
                $userId,
            );
        });
    }

    public function accrueTopUpStaking(
        ItcPackage $package,
        float $amount,
        int $userId,
        bool $writeLegacyActivity = true,
    ): StakingTransactionAccrual {
        $token = round($amount / app(TokenRateResolver::class)->currentRate(), 2);
        $profit = $token - $amount;

        return DB::transaction(function () use ($package, $profit, $userId, $token, $writeLegacyActivity) {
            if ($writeLegacyActivity) {
                activity('package')
                    ->performedOn($package)
                    ->causedBy($userId)
                    ->withProperties([
                        'package_amount' => $package->stakingTransactionAccruals->sum('amount'),
                        'percent' => $package->month_profit_percent,
                        'uuid' => $package->uuid,
                        'amount' => $token,
                        'package_type' => PackageTypeEnum::STAKING,
                        'exchange_rate' => app(TokenRateResolver::class)->currentRate(),
                    ])
                    ->log('top_up_package');
            }

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::TopUpBonus,
                $profit,
                $userId,
            );
        });
    }

    public function accrueStartBonus(ItcPackage $package, float $amount, int $ancestorId, int $buyerId): StakingTransactionAccrual
    {
        $currentRate = app(TokenRateResolver::class)->currentRate();

        return DB::transaction(function () use ($package, $ancestorId, $buyerId, $amount, $currentRate) {
            activity('package')
                ->performedOn($package)
                ->causedBy($ancestorId)
                ->withProperties([
                    'username' => User::query()->findOrFail($buyerId)->username,
                    'uuid' => $package->uuid,
                    'amount' => $amount,
                    'exchange_rate' => $currentRate,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('start_bonus_package');

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::StartBonus,
                $amount,
                $ancestorId,
                accrualRate: $currentRate,
            );
        });
    }

    public function accrueProfit(ItcPackage $package, float $amount, int $userId): StakingTransactionAccrual
    {
        $profit = round(($amount / 100) * $package->month_profit_percent, 2);
        $currentRate = app(TokenRateResolver::class)->currentRate();

        return DB::transaction(function () use ($package, $profit, $userId, $currentRate) {
            $balanceStaking = ItcPackage::query()
                ->active(PackageTypeEnum::STAKING)
                ->whereHas('transaction', fn ($query) => $query->where('user_id', $userId))
                ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
                ->get()
                ->sum(fn (ItcPackage $item): float => app(StakingPerformanceService::class)->forPackage($item)['total_tokens']);

            activity('package')
                ->performedOn($package)
                ->causedBy($userId)
                ->withProperties([
                    'profit' => $profit,
                    'percent' => $package->month_profit_percent,
                    'uuid' => $package->uuid,
                    'amount' => $balanceStaking,
                    'exchange_rate' => $currentRate,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('profit_accrued');

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::Profit,
                $profit,
                $userId,
                accrualRate: $currentRate,
            );
        });
    }

    public function accruePartnerBonus(
        ItcPackage $package,
        float $amount,
        int $userId,
        ?int $sourceUser = null,
        ?int $line = null,
    ): StakingTransactionAccrual {
        $currentRate = app(TokenRateResolver::class)->currentRate();

        return DB::transaction(function () use ($package, $amount, $userId, $sourceUser, $line, $currentRate) {
            $user = User::query()->findOrFail($sourceUser);

            activity('packages')
                ->performedOn($package)
                ->causedBy($user)
                ->withProperties([
                    'username' => User::query()->findOrFail($userId)->username,
                    'amount' => $amount,
                    'package_uuid' => $package->uuid,
                    'exchange_rate' => $currentRate,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('regular_premium_package');

            return $this->accrue(
                $package,
                StakingTransactionAccrualEnum::PartnerBonus,
                $amount,
                $userId,
                $sourceUser,
                $line,
                $currentRate,
            );
        });
    }

    public function accrue(
        ItcPackage $package,
        StakingTransactionAccrualEnum $type,
        float $amount,
        int $userId,
        ?int $sourceUser = null,
        ?int $line = null,
        ?float $accrualRate = null,
    ): StakingTransactionAccrual {
        return $package->stakingTransactionAccruals()->create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'accrual_rate' => $accrualRate,
            'source_user_id' => $sourceUser,
            'line' => $line,
        ]);
    }
}
