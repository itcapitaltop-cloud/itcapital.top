<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackageAddedManualProfit implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        $accrualType = StakingTransactionAccrualEnum::tryFrom(
            (string) $activity->getExtraProperty('accrual_type', StakingTransactionAccrualEnum::Profit->value)
        );

        return __('activity/admin.admin_package_added_manual_profit', [
            'uuid' => $activity->getExtraProperty('package_uuid', ''),
            'amount' => $activity->getExtraProperty('amount', 0),
            'accrual_type' => $this->resolveAccrualTypeLabel($accrualType),
        ]);
    }

    private function resolveAccrualTypeLabel(?StakingTransactionAccrualEnum $accrualType): string
    {
        return match ($accrualType) {
            StakingTransactionAccrualEnum::StartBonus => __('activity/admin.staking_accrual_type_start_bonus'),
            StakingTransactionAccrualEnum::PartnerBonus => __('activity/admin.staking_accrual_type_partner_bonus'),
            StakingTransactionAccrualEnum::TopUpBonus => __('activity/admin.staking_accrual_type_topup_bonus'),
            StakingTransactionAccrualEnum::Profit, null => __('activity/admin.staking_accrual_type_profit'),
        };
    }
}
