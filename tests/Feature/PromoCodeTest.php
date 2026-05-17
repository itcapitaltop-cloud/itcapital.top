<?php

use App\Actions\PromoCodes\GeneratePromoCodeAction;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Models\ItcPackage;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

function fundMainBalance(User $user, string $amount = '100.00000000'): void
{
    Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => $amount,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);
}

it('generates an unused promo code', function () {
    $promoCode = GeneratePromoCodeAction::make()->run(
        PackageTypeEnum::STANDARD,
        '25.00000000',
    );

    expect($promoCode->code)
        ->toStartWith('ITC-')
        ->and($promoCode->usages()->exists())->toBeFalse()
        ->and($promoCode->package_type)->toBe(PackageTypeEnum::STANDARD)
        ->and($promoCode->reduced_minimum_amount)->toBe('25.00000000');
});

it('redeems a valid promo code once during package purchase', function () {
    $user = User::factory()->create();
    fundMainBalance($user);

    $promoCode = PromoCode::factory()->create([
        'code' => 'PROMO50',
        'package_type' => PackageTypeEnum::STANDARD,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'PROMO50')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect($promoCode->usages()->where('user_id', $user->id)->exists())->toBeTrue();

    $transaction = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and((string) $transaction->amount)->toBe('50.00000000')
        ->and(ItcPackage::query()->where('uuid', $transaction->uuid)->exists())->toBeTrue();
});

it('allows different users to use the same promo code', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    fundMainBalance($firstUser);
    fundMainBalance($secondUser);

    $promoCode = PromoCode::factory()->create([
        'code' => 'MULTI50',
        'package_type' => PackageTypeEnum::STANDARD,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($firstUser);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'MULTI50')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $this->actingAs($secondUser);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'MULTI50')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect($promoCode->usages()->count())->toBe(2)
        ->and($promoCode->usages()->where('user_id', $firstUser->id)->exists())->toBeTrue()
        ->and($promoCode->usages()->where('user_id', $secondUser->id)->exists())->toBeTrue();
});

it('rejects same user reusing a promo code for the same package type', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '200.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'ONCE50',
        'package_type' => PackageTypeEnum::STANDARD,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'ONCE50')
        ->call('buyPackage')
        ->assertHasNoErrors();

    Livewire::test(ItcPackages::class)
        ->set('amount', '60')
        ->set('promoCode', 'ONCE50')
        ->call('buyPackage')
        ->assertHasErrors(['promoCode']);

    expect($promoCode->usages()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Transaction::query()
            ->where('user_id', $user->id)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->count())->toBe(1);
});

it('allows same user to use promo code for different package types', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '300.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'SAME50',
        'package_type' => PackageTypeEnum::VIP,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'SAME50')
        ->call('buyPackage')
        ->assertHasErrors(['promoCode']);

    expect($promoCode->usages()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects invalid and mismatched promo codes without creating a package', function (string $code, array $attributes) {
    $user = User::factory()->create();
    fundMainBalance($user);

    if ($attributes !== []) {
        PromoCode::factory()->create($attributes);
    }

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', $code)
        ->call('buyPackage')
        ->assertHasErrors(['promoCode']);

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->exists())->toBeFalse();
})->with([
    'invalid code' => ['UNKNOWN50', []],
    'mismatched package type' => ['VIP50', [
        'code' => 'VIP50',
        'package_type' => PackageTypeEnum::VIP,
        'reduced_minimum_amount' => '50.00000000',
    ]],
]);

it('rejects package purchase below the promo reduced threshold', function () {
    $user = User::factory()->create();
    fundMainBalance($user);

    PromoCode::factory()->create([
        'code' => 'MIN75',
        'package_type' => PackageTypeEnum::STANDARD,
        'reduced_minimum_amount' => '75.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('amount', '50')
        ->set('promoCode', 'MIN75')
        ->call('buyPackage')
        ->assertHasErrors(['amount']);

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->exists())->toBeFalse();
});
