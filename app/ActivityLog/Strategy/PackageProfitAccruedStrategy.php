<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class PackageProfitAccruedStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/user.profit_accrued', [
            'profit' => $activity->getExtraProperty('profit', 0),
            'uuid' => $activity->getExtraProperty('uuid', 0),
            'amount' => $activity->getExtraProperty('amount', 0),
            'exchange_rate' => $activity->getExtraProperty('exchange_rate', 0.1),
        ]);
    }
}
