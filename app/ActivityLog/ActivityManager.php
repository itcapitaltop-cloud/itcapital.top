<?php

declare(strict_types=1);

namespace App\ActivityLog;

use App\ActivityLog\Strategy\Admin\AdminPackageChangedAmount;
use App\ActivityLog\Strategy\Admin\AdminPackageChangedPercent;
use App\ActivityLog\Strategy\Admin\AdminPackagePurchasedStrategy;
use App\ActivityLog\Strategy\Admin\AdminPackageStakingPercentStrategy;
use App\ActivityLog\Strategy\PackageProfitAccruedStrategy;
use App\ActivityLog\Strategy\PackageStagingPurchasedStrategy;
use Spatie\Activitylog\Contracts\Activity;

final class ActivityManager
{
    public function resolver(Activity $activity): string
    {
        return match ($activity->description) {
            'package_purchased' => new PackageStagingPurchasedStrategy()->run($activity),
            'profit_accrued' => new PackageProfitAccruedStrategy()->run($activity),
            'admin_package_staking_changed_percentage' => new AdminPackageStakingPercentStrategy()->run($activity),
            'admin_package_purchased' => new AdminPackagePurchasedStrategy()->run($activity),
            'admin_package_changed_amount' => new AdminPackageChangedAmount()->run($activity),
            'admin_package_changed_percentage' => new AdminPackageChangedPercent()->run($activity),
            default => 'Не известное событие'
        };
    }
}
