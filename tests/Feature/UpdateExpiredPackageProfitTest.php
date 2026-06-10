<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

/**
 * Guards `packages:expired-set-profit` (UpdateExpiredPackageProfit).
 *
 * Money-critical: after a package's work_to passes, its rate drops to 5.5%
 * (when the body is still > 100) or to 0% (when the body has been drained).
 * With package durations now configurable per PackageDefinition, packages reach
 * this transition far sooner than the historical 30-week default, so the rule
 * must stay exactly as designed.
 */
function makeExpiringPackage(float $balance, string $percent, Carbon $workTo): ItcPackage
{
    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => $balance,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    return ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => $percent,
        'work_to' => $workTo,
    ]);
}

it('drops an expired package with a funded body to the 5.5% residual rate', function () {
    $package = makeExpiringPackage(1000, '8.20', now()->subDay());

    $this->artisan('packages:expired-set-profit')->assertSuccessful();

    expect((float) $package->fresh()->month_profit_percent)->toBe(5.5);
});

it('zeroes the rate of an expired package whose body is at or below 100', function () {
    $package = makeExpiringPackage(50, '8.20', now()->subDay());

    $this->artisan('packages:expired-set-profit')->assertSuccessful();

    expect((float) $package->fresh()->month_profit_percent)->toBe(0.0);
});

it('leaves a package that has not yet reached work_to untouched', function () {
    $package = makeExpiringPackage(1000, '8.20', now()->addMonth());

    $this->artisan('packages:expired-set-profit')->assertSuccessful();

    expect((float) $package->fresh()->month_profit_percent)->toBe(8.2);
});

it('does not re-touch a package already at the residual rate', function () {
    $package = makeExpiringPackage(1000, '5.50', now()->subDay());
    $updatedAt = $package->fresh()->updated_at;

    Carbon::setTestNow(now()->addMinute());

    $this->artisan('packages:expired-set-profit')->assertSuccessful();

    $fresh = $package->fresh();

    expect((float) $fresh->month_profit_percent)->toBe(5.5)
        ->and($fresh->updated_at->equalTo($updatedAt))->toBeTrue();

    Carbon::setTestNow();
});

it('only affects the package targeted by --uuid', function () {
    $target = makeExpiringPackage(1000, '8.20', now()->subDay());
    $other = makeExpiringPackage(1000, '8.20', now()->subDay());

    $this->artisan('packages:expired-set-profit', ['--uuid' => $target->uuid])->assertSuccessful();

    expect((float) $target->fresh()->month_profit_percent)->toBe(5.5)
        ->and((float) $other->fresh()->month_profit_percent)->toBe(8.2);
});
