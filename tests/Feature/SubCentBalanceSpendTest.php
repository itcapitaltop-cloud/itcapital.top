<?php

declare(strict_types=1);

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Livewire\Account\ItcStaking\Index as ItcStakingIndex;
use App\Models\ItcPackage;
use App\Models\TokenRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\PackageDefinitionResolver;
use App\Services\Token\TokenRateResolver;
use App\Services\User\SpendableBalanceResolver;
use App\Services\User\UserBalanceCalculator;
use App\Settings\GeneralSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * `transactions.amount` is decimal(16, 8) but every balance is displayed at 2 decimals
 * with half-up rounding, so the figure on screen can be up to 0.005 HIGHER than the real
 * balance. A user (or admin) typing that figure used to get "Недостаточно средств".
 *
 * The balance shown is never lowered; instead the debit is clamped to the real balance.
 */

/**
 * Give the user a main balance whose 3rd decimal rounds the display UP,
 * e.g. 1004.23617400 → displayed as "1004.24".
 */
function giveSubCentMainBalance(User $user, string $amount = '1004.23617400'): void
{
    Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $amount,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);
}

function realMainBalance(User $user): string
{
    return app(UserBalanceCalculator::class)
        ->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);
}

/**
 * The raw stored amount. `Transaction::$casts` declares `amount => decimal:2`, so
 * reading the attribute through the model hides exactly the sub-cent digits under test.
 */
function rawTransactionAmount(string $uuid): string
{
    return (string) DB::table('transactions')->where('uuid', $uuid)->value('amount');
}

it('displays a sub-cent balance rounded up, which is the amount the user types', function () {
    $resolver = app(SpendableBalanceResolver::class);

    expect($resolver->toDisplayScale('1004.23617400'))->toBe('1004.24')
        ->and($resolver->coversRequestedAmount('1004.23617400', '1004.24'))->toBeTrue()
        ->and($resolver->clampToBalance('1004.23617400', '1004.24'))->toBe('1004.23617400')
        ->and($resolver->wasClamped('1004.23617400', '1004.24'))->toBeTrue();
});

it('still rejects an amount above the displayed balance', function () {
    $resolver = app(SpendableBalanceResolver::class);

    expect($resolver->coversRequestedAmount('1004.23617400', '1004.25'))->toBeFalse()
        ->and($resolver->coversRequestedAmount('1004.23617400', '2000'))->toBeFalse();
});

it('does not clamp when the request fits inside the real balance', function () {
    $resolver = app(SpendableBalanceResolver::class);

    expect($resolver->clampToBalance('1004.23617400', '500'))->toBe('500')
        ->and($resolver->wasClamped('1004.23617400', '500'))->toBeFalse();
});

it('lets a user stake the displayed balance and lands the balance on zero', function () {
    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate(now(), 0.10);

    $user = User::factory()->create();
    giveSubCentMainBalance($user);
    $this->actingAs($user);

    expect(realMainBalance($user))->toBe('1004.23617400');

    Livewire::test(ItcStakingIndex::class)
        ->set('amount', '1004.24')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $package = ItcPackage::query()
        ->where('type', PackageTypeEnum::STAKING)
        ->whereRelation('transaction', 'user_id', $user->id)
        ->sole();

    expect((float) rawTransactionAmount($package->uuid))->toBe(1004.236174)
        ->and((float) realMainBalance($user))->toBe(0.0);
});

it('lets a user top up an ITC package with the displayed balance', function () {
    $user = User::factory()->create();
    giveSubCentMainBalance($user);
    $this->actingAs($user);

    $bodyTransaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => '0',
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $bodyTransaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.20',
    ]);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '1004.24')
        ->call('topUpNeeded', $package->uuid)
        ->assertHasNoErrors();

    expect((float) rawTransactionAmount($bodyTransaction->uuid))->toBe(1004.236174);
});

it('lets a user buy an ITC package with the displayed balance', function () {
    $user = User::factory()->create();
    giveSubCentMainBalance($user);
    $this->actingAs($user);

    $definition = app(PackageDefinitionResolver::class)->resolve(PackageTypeEnum::STANDARD);

    Livewire::test(Packages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '1004.24')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $package = ItcPackage::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->whereRelation('transaction', 'user_id', $user->id)
        ->sole();

    expect((float) rawTransactionAmount($package->uuid))->toBe(1004.236174)
        ->and((float) realMainBalance($user))->toBe(0.0);
});

it('does not create money out of thin air when the displayed balance is spent', function () {
    $resolver = app(SpendableBalanceResolver::class);

    // The clamped amount can never exceed the real balance, whatever was requested.
    foreach (['1004.24', '1004.2361740001', '9999999'] as $requested) {
        expect((float) $resolver->clampToBalance('1004.23617400', $requested))
            ->toBeLessThanOrEqual(1004.236174);
    }
});
