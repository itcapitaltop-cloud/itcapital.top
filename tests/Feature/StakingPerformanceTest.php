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
        ->and($performance['current_value_usd'])->toBe(2460.0);
});

it('includes accruals with their own historical rates in unrealized pnl', function () {
    Carbon::setTestNow('2026-03-15 10:00:00');

    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-03-01', 0.10);

    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 10);

    Carbon::setTestNow('2026-04-02 10:00:00');
    app(TokenRateResolver::class)->upsertRate('2026-04-01', 0.12);
    app(StakingPurchaseService::class)->addPurchase($package->fresh(), 10, $user->id);

    Carbon::setTestNow('2026-05-01 10:00:00');
    app(TokenRateResolver::class)->upsertRate('2026-05-01', 0.13);
    app(StakingAccrualService::class)->accrueProfit(
        $package->fresh(),
        44.63,
        $user->id
    );

    Carbon::setTestNow('2026-05-10 10:00:00');
    app(TokenRateResolver::class)->upsertRate('2026-05-10', 0.14);
    app(StakingAccrualService::class)->accrueStartBonus(
        $package->fresh(),
        1.50,
        $user->id,
        $user->id
    );

    Carbon::setTestNow('2026-05-20 10:00:00');
    app(TokenRateResolver::class)->upsertRate('2026-05-20', 0.15);
    $sourceUser = User::factory()->create();
    app(StakingAccrualService::class)->accruePartnerBonus(
        $package->fresh(),
        2.25,
        $user->id,
        $sourceUser->id,
        1
    );

    Carbon::setTestNow('2026-06-01 10:00:00');
    app(TokenRateResolver::class)->upsertRate('2026-06-01', 0.16);

    $package = ItcPackage::query()
        ->whereKey($package->id)
        ->with(['transaction', 'stakingPurchases', 'stakingTransactionAccruals'])
        ->firstOrFail();

    $performance = app(StakingPerformanceService::class)->forPackage($package);

    expect($performance['invested_usd'])->toBe(20.0)
        ->and($performance['purchased_tokens'])->toBe(183.33)
        ->and($performance['yield_tokens'])->toBe(4.64)
        ->and($performance['total_tokens'])->toBe(187.97)
        ->and($performance['current_value_usd'])->toBe(30.08)
        ->and($performance['unrealized_pnl_usd'])->toBe(9.41)
        ->and((float) $package->stakingTransactionAccruals->firstWhere('type', StakingTransactionAccrualEnum::Profit)?->accrual_rate)->toBe(0.13)
        ->and((float) $package->stakingTransactionAccruals->firstWhere('type', StakingTransactionAccrualEnum::StartBonus)?->accrual_rate)->toBe(0.14)
        ->and((float) $package->stakingTransactionAccruals->firstWhere('type', StakingTransactionAccrualEnum::PartnerBonus)?->accrual_rate)->toBe(0.15);
});

it('counts manual token grants but not purchase shadow bonuses on a package with purchases', function () {
    Carbon::setTestNow('2026-03-15 10:00:00');

    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-03-01', 0.10);

    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 100);

    app(StakingAccrualService::class)->accrue(
        $package->fresh(),
        StakingTransactionAccrualEnum::ManualTokens,
        100,
        $user->id,
    );

    $package = ItcPackage::query()
        ->whereKey($package->id)
        ->with(['transaction', 'stakingPurchases', 'stakingTransactionAccruals'])
        ->firstOrFail();

    $performance = app(StakingPerformanceService::class)->forPackage($package);

    // createPackage() also writes a TopUpBonus shadow of the purchase, which
    // must stay out of both sums, while the manual grant lands in the yield.
    expect($package->stakingTransactionAccruals->where('type', StakingTransactionAccrualEnum::TopUpBonus))->toHaveCount(1)
        ->and($performance['purchased_tokens'])->toBe(1000.0)
        ->and($performance['yield_tokens'])->toBe(100.0)
        ->and($performance['total_tokens'])->toBe(1100.0);
});

afterEach(function () {
    Carbon::setTestNow();
});
