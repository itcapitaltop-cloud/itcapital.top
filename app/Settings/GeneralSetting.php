<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class GeneralSetting extends Settings
{
    public int $regular_staking_percent;
    public int $start_bonus_staking_percent;
    public float $exchange_rate_itc;

    public static function group(): string
    {
        return 'general';
    }
}
