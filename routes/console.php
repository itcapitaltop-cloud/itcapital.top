<?php

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:backup-processing-command')
    ->daily()
    ->sendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('quotes:cache')
    ->cron('*/1 * * * *');

Schedule::command('itc:close-present-packages')
    ->cron('* * * * *');

Schedule::command('quotes:cache-alphavantage')
    ->dailyAt('08:00');

Schedule::command('regular-premium:accrual')
    ->cron('59 23 * * 1')
    ->when(function () {
        // Якорный понедельник
        $anchor = CarbonImmutable::create(2025, 8, 11)->startOfWeek(CarbonInterface::MONDAY);
        $nowW   = now()->startOfWeek(CarbonInterface::MONDAY);
        return $anchor->diffInWeeks($nowW) % 2 === 0; // только “каждый второй”
    })
    ->sendOutputTo(storage_path('logs/scheduler.log'));
