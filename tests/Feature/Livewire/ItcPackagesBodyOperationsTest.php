<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserBalanceCalculator;
use Livewire\Livewire;

/**
 * Guards the package-body money paths: withdrawing part of the body to the main
 * balance (withdrawPackageBalance) and topping the body up (topUpNeeded / докидывание).
 */
function makeBodyPackage(User $user, float $body): ItcPackage
{
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $body,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    return ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.20',
    ]);
}

function balanceForMain(User $user): float
{
    return (float) app(UserBalanceCalculator::class)
        ->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);
}

it('withdraws part of the package body to the main balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makeBodyPackage($user, 1000);
    $balanceBefore = balanceForMain($user);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '500')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertHasNoErrors();

    $trx = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE)
        ->sole();

    expect((float) $trx->amount)->toBe(500.0)
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(1)
        ->and(balanceForMain($user))->toBe(round($balanceBefore + 500, 2))
        // Body still 500 (> 100) → rate must be preserved.
        ->and((float) $package->fresh()->month_profit_percent)->toBe(8.2);
});

it('zeroes the rate when the remaining body drops below 100', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makeBodyPackage($user, 1000);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '950')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertHasNoErrors();

    // Remaining body = 50 (< 100) → rate forced to 0.
    expect((float) $package->fresh()->month_profit_percent)->toBe(0.0);
});

it('rejects withdrawing more than the available package body', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makeBodyPackage($user, 1000);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '1500')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertHasErrors('withdrawPackageAmount');

    expect(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->exists())->toBeFalse();
});

it('tops the package body up from the main balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Net main balance must cover the top-up: deposit offsets the BUY_PACKAGE
    // credit (1000) and leaves 300 spendable.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '1300.00',
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $package = makeBodyPackage($user, 1000);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '200')
        ->call('topUpNeeded', $package->uuid);

    expect((float) $package->transaction()->first()->amount)->toBe(1200.0);
});

it('rejects a top-up that exceeds the main balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Net main balance = 1100 deposit - 1000 buy = 100, below the 200 top-up.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '1100.00',
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $package = makeBodyPackage($user, 1000);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '200')
        ->call('topUpNeeded', $package->uuid)
        ->assertHasErrors('withdrawPackageAmount');

    expect((float) $package->transaction()->first()->amount)->toBe(1000.0);
});
