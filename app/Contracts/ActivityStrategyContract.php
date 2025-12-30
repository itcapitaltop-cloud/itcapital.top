<?php

namespace App\Contracts;

use Spatie\Activitylog\Contracts\Activity;

interface ActivityStrategyContract
{
    public function run(Activity $activity): string;
}
