<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.regular_staking_percent', 100);
        $this->migrator->add('general.start_bonus_staking_percent', 10);
        $this->migrator->add('general.exchange_rate_itc', 0.1);
    }
};
