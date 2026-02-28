<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class PackageTopUpStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/user.top_up_package', [
            'amount' => $activity->getExtraProperty('amount', 0),
            'uuid' => $activity->getExtraProperty('uuid', 0),
        ]);
    }
}
