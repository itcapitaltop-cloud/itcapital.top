<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackageChangedPercent implements ActivityStrategyContract
{

    public function run(Activity $activity): string
    {
        return __('activity/admin.admin_package_changed_percentage', [
            'uuid' => $activity->getExtraProperty('package_uuid', ''),
            'amount' => $activity->getExtraProperty('amount', 0),
            'percent' => $activity->getExtraProperty('old_percent', 0),
        ]);
    }
}
