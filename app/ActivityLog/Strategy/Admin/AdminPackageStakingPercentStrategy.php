<?php

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

class AdminPackageStakingPercentStrategy implements ActivityStrategyContract
{
    public function run(Activity $activity): string
    {
        return __('activity/admin.admin_package_staking_changed_percentage', [
            'percent' => $activity->getExtraProperty('percent', 2),
        ]);
    }
}
