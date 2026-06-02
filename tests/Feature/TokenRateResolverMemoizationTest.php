<?php

declare(strict_types=1);

use App\Models\TokenRate;
use App\Services\Token\TokenRateResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow('2026-06-02 12:00:00');
    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-03-01', 0.10);
    app(TokenRateResolver::class)->upsertRate('2026-04-01', 0.12);
});

it('memoizes repeated rate lookups within a single resolver instance', function () {
    $resolver = app(TokenRateResolver::class);

    // Warm the cache once for every date/lookup exercised below.
    $resolver->rateForDate('2026-05-01');
    $resolver->currentRate();
    $resolver->earliestRate();
    $resolver->earliestEffectiveFrom();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    // Repeated identical lookups must not touch the database again.
    for ($i = 0; $i < 50; $i++) {
        $resolver->rateForDate('2026-05-01');
        $resolver->currentRate();
        $resolver->earliestRate();
        $resolver->earliestEffectiveFrom();
    }

    expect($queries)->toBe(0)
        ->and($resolver->rateForDate('2026-03-15'))->toBe(0.10)
        ->and($resolver->rateForDate('2026-04-15'))->toBe(0.12);
});

it('shares a single memoized resolver instance through the container', function () {
    expect(app(TokenRateResolver::class))->toBe(app(TokenRateResolver::class));
});
