<?php

namespace App\Contracts;

use Spatie\Activitylog\Contracts\Activity;

interface ActivityStrategyContract
{
    public function handle(Activity $activity): string;
}
