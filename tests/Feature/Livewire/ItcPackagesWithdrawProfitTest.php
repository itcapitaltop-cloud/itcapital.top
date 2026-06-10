<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserBalanceCalculator;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Guards the dividend-withdrawal money path (Packages::withdrawProfit).
 *
 * Verifies the withdrawn amount equals the current dividends, that the dividends
 * are consumed exactly once (lockForUpdate + idempotency), and that the user's
 * MAIN balance increases by precisely the withdrawn amount through the single
 * source of truth (UserBalanceCalculator).
 */
function buyPackageWithDividends(User $user, float $body, string $dividends): ItcPackage
{
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $body,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => $dividends,
    ]);

    return $package;
}

function mainBalance(User $user): string
{
    return app(UserBalanceCalculator::class)
        ->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);
}

it('withdraws the full dividends amount to the main balance once', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Fund a real main balance the way a deposit would.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '500.00',
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $package = buyPackageWithDividends($user, 1000, '57.07');

    $balanceBefore = (float) mainBalance($user);

    Livewire::test(Packages::class)
        ->call('withdrawProfit', $package->uuid)
        ->assertHasNoErrors();

    $withdrawTrx = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT)
        ->sole();

    expect((float) $withdrawTrx->amount)->toBe(57.07)
        ->and($withdrawTrx->accepted_at)->not->toBeNull()
        ->and((float) mainBalance($user))->toBe(round($balanceBefore + 57.07, 2));
});

it('refuses a second withdrawal once dividends are already drained', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = buyPackageWithDividends($user, 1000, '57.07');

    $component = Livewire::test(Packages::class);

    $component->call('withdrawProfit', $package->uuid)
        ->assertHasNoErrors();

    $component->call('withdrawProfit', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'error');

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT)
        ->count())->toBe(1);
});

it('rejects withdrawal when there are no dividends to withdraw', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 1000,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    Livewire::test(Packages::class)
        ->call('withdrawProfit', $package->uuid)
        ->assertDispatched('new-system-notification', type: 'error');

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT)
        ->exists())->toBeFalse();
});
