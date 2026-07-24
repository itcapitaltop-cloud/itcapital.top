<?php

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
    ->cron('*/12 * * * *')
    ->skip(fn (): bool => app()->environment(['local', 'dev']));

Schedule::command('itc:close-present-packages')
    ->cron('* * * * *');

Schedule::command('quotes:cache-alphavantage')
    ->dailyAt('08:00')
    ->skip(fn (): bool => app()->environment(['local', 'dev']));

Schedule::command('regular-premium:accrual')
    ->weeklyOn(1, '23:50')
    ->sendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('staking-regular-premium:accrual')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/staking-scheduler.log'));

Schedule::command('profit-accrual:itc-staking')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('partner:rank-maintenance')
    ->monthlyOn(1, '00:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->when(fn (): bool => (bool) config('rank.maintenance.enabled', false))
    ->sendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('summary:reconcile')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->sendOutputTo(storage_path('logs/summary-reconcile.log'));

Schedule::command('summary:warm-dashboard')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
