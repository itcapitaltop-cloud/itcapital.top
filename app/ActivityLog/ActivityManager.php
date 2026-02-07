<?php

declare(strict_types=1);

namespace App\ActivityLog;

use Spatie\Activitylog\Contracts\Activity;

final class ActivityManager
{
    public function resolve(Activity $activity): string
    {
        $strategies = config('activity.strategies');
        $strategyClass = $strategies[$activity->description] ?? null;

        if ($strategyClass && class_exists($strategyClass)) {
            /** @var \App\Contracts\ActivityStrategyContract $strategy */
            $strategy = app($strategyClass);

            return $strategy->handle($activity);
        }

        return __('activity/unknown_event');
    }
}
