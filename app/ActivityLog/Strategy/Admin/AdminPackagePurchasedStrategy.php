<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackagePurchasedStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/admin.admin_package_purchased', [
            'uuid' => $activity->getExtraProperty('package_uuid', ''),
            'amount' => $activity->getExtraProperty('amount', 0),
        ]);
    }
}
