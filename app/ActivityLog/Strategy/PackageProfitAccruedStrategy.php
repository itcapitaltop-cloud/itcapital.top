<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class PackageProfitAccruedStrategy implements ActivityStrategyContract
{
    public function run(Activity $activity): string
    {
        return __('activity/user.profit_accrued', [
            'profit' => $activity->getExtraProperty('profit', 0),
            'uuid' => $activity->getExtraProperty('package_uuid', 0),
            'package_amount' => $activity->getExtraProperty('transaction_amount', 0),
        ]);
    }
}
