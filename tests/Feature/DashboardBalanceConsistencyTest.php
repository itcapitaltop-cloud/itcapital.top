<?php

declare(strict_types=1);

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Services\User\UserBalanceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow('2026-06-02 12:00:00');
});

it('returns the live balance when no cutoff is given', function () {
    $user = User::factory()->create();

    // +100 debit, -40 credit => 60
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 100,
        'accepted_at' => now()->subDays(10),
    ]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_OUT,
        'amount' => 40,
        'accepted_at' => null, // pending credit still reduces the live balance
    ]);

    expect((float) app(UserBalanceCalculator::class)->balanceFor($user->id, BalanceTypeEnum::PARTNER))
        ->toBe(60.0);
});

it('ignores staking accruals on the partner balance', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 100,
        'accepted_at' => now()->subDays(10),
    ]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::STAKING_START_BONUS_ACCRUAL,
        'amount' => 8.10,
        'accepted_at' => now()->subDays(5),
    ]);

    // Staking bonus is intentionally excluded => balance stays 100.
    expect((float) app(UserBalanceCalculator::class)->balanceFor($user->id, BalanceTypeEnum::PARTNER))
        ->toBe(100.0);
});

it('reconstructs the balance as of a past moment', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 100,
        'accepted_at' => now()->subDays(10),
    ]);
    // Accrued after the cutoff => must not be counted at the cutoff.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 25,
        'accepted_at' => now()->subDays(2),
    ]);

    $calc = app(UserBalanceCalculator::class);

    expect((float) $calc->balanceFor($user->id, BalanceTypeEnum::PARTNER, now()->subDays(5)))->toBe(100.0)
        ->and((float) $calc->balanceFor($user->id, BalanceTypeEnum::PARTNER))->toBe(125.0);
});

it('keeps partnerPeriodStats consistent with the unified balance', function () {
    $user = User::factory()->create();
    Auth::login($user);

    // Balance before the week window.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 100,
        'accepted_at' => now()->subDays(20),
    ]);
    // Earned 30 within the last week.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER,
        'amount' => 30,
        'accepted_at' => now()->subDays(2),
    ]);

    $stats = app(TransactionRepository::class)->partnerPeriodStats(now()->subWeek());

    expect($stats['start'])->toBe(100.0)
        ->and($stats['end'])->toBe(130.0)
        ->and($stats['delta'])->toBe(30.0);
});

it('reuses a point-in-time snapshot instead of re-querying it', function () {
    $user = User::factory()->create();
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
        'amount' => 100,
        'accepted_at' => now()->subDays(3),
    ]);

    $calc = app(UserBalanceCalculator::class);
    $asOf = now();

    DB::connection()->enableQueryLog();
    $first = $calc->balanceFor($user->id, BalanceTypeEnum::PARTNER, $asOf);
    $second = $calc->balanceFor($user->id, BalanceTypeEnum::PARTNER, $asOf);

    expect($first)->toBe($second)
        ->and(DB::connection()->getQueryLog())->toHaveCount(1);
});
