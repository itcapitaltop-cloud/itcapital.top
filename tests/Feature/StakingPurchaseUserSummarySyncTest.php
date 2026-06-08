<?php

declare(strict_types=1);

use App\Models\TokenRate;
use App\Models\User;
use App\Models\UserSummary;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;

it('keeps user_summary investments_sum in sync after topping up a staking package', function () {
    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate(now(), 0.10);

    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 1000);

    expect(UserSummary::query()->find($user->id)->investments_sum)->toBe('-1000.00');

    app(StakingPurchaseService::class)->addPurchase($package->fresh(), 1100, $user->id);

    expect((float) UserSummary::query()->find($user->id)->investments_sum)->toBe(-2100.0);
});
