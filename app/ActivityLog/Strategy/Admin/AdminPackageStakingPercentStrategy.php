<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackageStakingPercentStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/admin.admin_package_staking_changed_percentage', [
            'percent' => $activity->getExtraProperty('percent', 2),
        ]);
    }
}
