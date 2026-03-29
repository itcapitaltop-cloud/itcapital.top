<?php

declare(strict_types=1);

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\ItcPackage;
use App\Models\TokenRate;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\Package\Staking\StakingPerformanceService;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;
use Carbon\Carbon;

it('calculates staking performance with rate history and multiple purchases', function () {
    Carbon::setTestNow('2026-03-15 10:00:00');

    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-03-01', 0.10);

    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 1000);

    Carbon::setTestNow('2026-04-02 10:00:00');

    app(TokenRateResolver::class)->upsertRate('2026-04-01', 0.12);
    app(StakingPurchaseService::class)->addPurchase($package->fresh(), 1200, $user->id);

    app(StakingAccrualService::class)->accrue(
        $package->fresh(),
        StakingTransactionAccrualEnum::Profit,
        500,
        $user->id
    );

    $package = ItcPackage::query()
        ->whereKey($package->id)
        ->with(['transaction', 'stakingPurchases', 'stakingTransactionAccruals'])
        ->firstOrFail();

    $performance = app(StakingPerformanceService::class)->forPackage($package);

    expect($package->type)->toBe(PackageTypeEnum::STAKING)
        ->and($package->stakingPurchases)->toHaveCount(2)
        ->and(app(TokenRateResolver::class)->rateForDate('2026-03-31'))->toBe(0.10)
        ->and(app(TokenRateResolver::class)->rateForDate('2026-04-02'))->toBe(0.12)
        ->and((float) $package->transaction->amount)->toBe(2200.0)
        ->and($performance['invested_usd'])->toBe(2200.0)
        ->and($performance['purchased_tokens'])->toBe(20000.0)
        ->and($performance['yield_tokens'])->toBe(500.0)
        ->and($performance['total_tokens'])->toBe(20500.0)
        ->and($performance['unrealized_pnl_usd'])->toBe(200.0)
        ->and($performance['current_value_usd'])->toBe(2460.0)
        ->and($performance['total_profit_usd'])->toBe(260.0);
});

afterEach(function () {
    Carbon::setTestNow();
});
