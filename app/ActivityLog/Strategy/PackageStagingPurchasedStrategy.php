<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class PackageStagingPurchasedStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/user.package_purchased', [
            'amount' => $activity->getExtraProperty('amount', 0),
            'uuid' => $activity->getExtraProperty('package_uuid', 0),
        ]);
    }
}
