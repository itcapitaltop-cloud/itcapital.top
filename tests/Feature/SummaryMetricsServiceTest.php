<?php

declare(strict_types=1);

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\SummaryMetricsService;
use Carbon\Carbon;

/**
 * Seeds one representative transaction of each metric-relevant type for a user.
 */
function seedSummaryFixturesFor(User $user): void
{
    // Accepted MAIN deposit: 100 (deposits + main balance)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'amount' => 100,
        'accepted_at' => now(),
    ]);

    // Accepted MAIN withdraw: 40 (withdraws + main balance as credit)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'amount' => 40,
        'accepted_at' => now(),
    ]);

    // Partner inflow: 50 (partner balance)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 50,
        'accepted_at' => now(),
    ]);

    // Regular premium: +70 accrual (debit), -20 mirror (credit) => net 50
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL,
        'amount' => 70,
        'accepted_at' => now(),
    ]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER_MIRROR,
        'amount' => 20,
        'accepted_at' => now(),
    ]);
}

beforeEach(function () {
    Carbon::setTestNow('2026-06-02 12:00:00');
});

it('computes transaction metrics for a real user', function () {
    $user = User::factory()->create(['is_test' => false]);
    seedSummaryFixturesFor($user);

    $snapshot = app(SummaryMetricsService::class)->refresh();

    expect($snapshot['users']['total'])->toBe(1)
        ->and($snapshot['deposits']['total_count'])->toBe(1)
        ->and($snapshot['deposits']['total_sum'])->toBe(100.0)
        ->and($snapshot['withdraws']['total_count'])->toBe(1)
        ->and($snapshot['withdraws']['total_sum'])->toBe(40.0)
        ->and($snapshot['balances']['main'])->toBe(60.0)
        ->and($snapshot['balances']['partner'])->toBe(50.0)
        ->and($snapshot['balances']['regular_premium'])->toBe(50.0)
        ->and($snapshot['packages'])->toHaveKeys([
            PackageTypeEnum::PRIVILEGE->value,
            PackageTypeEnum::STANDARD->value,
            PackageTypeEnum::VIP->value,
            PackageTypeEnum::PRESENT->value,
            PackageTypeEnum::STAKING->value,
        ])
        ->and($snapshot['packages'][PackageTypeEnum::PRIVILEGE->value])->toBe(0.0);
});

it('excludes test users and banned users from every metric', function () {
    $real = User::factory()->create(['is_test' => false]);
    seedSummaryFixturesFor($real);

    $baseline = app(SummaryMetricsService::class)->refresh();

    // Add identical data for a test user and a banned user.
    $testUser = User::factory()->create(['is_test' => true]);
    seedSummaryFixturesFor($testUser);

    $bannedUser = User::factory()->create(['is_test' => false, 'banned_at' => now()]);
    seedSummaryFixturesFor($bannedUser);

    $afterNoise = app(SummaryMetricsService::class)->refresh();

    // Not a single metric may move because of test/banned activity.
    expect($afterNoise)->toEqual($baseline);
});
