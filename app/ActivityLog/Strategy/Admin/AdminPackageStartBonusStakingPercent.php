<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackageStartBonusStakingPercent implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        // TODO: Implement handle() method.
    }
}
