<?php

use App\Actions\PromoCodes\GeneratePromoCodeAction;
use App\Actions\Staking\CreateStakingPackageAction;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PromoCodes\PackagePromoCodeService;
use Livewire\Livewire;

const PROMO_ADMIN_INDEX_REFERER = '/itcapitalmoonshineadminpanel/resource/promo-code-resource/index-page';

function activePackageDefinition(PackageTypeEnum $packageType, string $minimumAmount = '250.00000000'): PackageDefinition
{
    $definition = PackageDefinition::query()
        ->where('slug', $packageType->value)
        ->first();

    if ($definition instanceof PackageDefinition) {
        $definition->forceFill([
            'type' => $packageType,
            'name' => $packageType->getName(),
            'min_start_amount' => $minimumAmount,
            'default_profit_percent' => '5.00',
            'duration_months' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ])->save();

        return $definition->refresh();
    }

    return PackageDefinition::factory()->create([
        'slug' => $packageType->value,
        'type' => $packageType,
        'name' => $packageType->getName(),
        'min_start_amount' => $minimumAmount,
        'default_profit_percent' => '5.00',
        'duration_months' => 1,
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function activeStandardPackageDefinition(string $minimumAmount = '250.00000000'): PackageDefinition
{
    return activePackageDefinition(PackageTypeEnum::STANDARD, $minimumAmount);
}

function fundMainBalance(User $user, string $amount = '500.00000000'): void
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
    $definition = activeStandardPackageDefinition();

    $promoCode = GeneratePromoCodeAction::make()->run(
        $definition,
        '25.00000000',
    );

    expect($promoCode->code)
        ->toStartWith('ITC-')
        ->and($promoCode->usages()->exists())->toBeFalse()
        ->and($promoCode->package_definition_id)->toBe($definition->id)
        ->and($promoCode->reduced_minimum_amount)->toBe('25.00000000');
});

it('generates promo codes for active privilege and vip package definitions', function (PackageTypeEnum $packageType) {
    $definition = activePackageDefinition($packageType, '5000.00000000');

    $promoCode = GeneratePromoCodeAction::make()->run(
        $definition,
        '100.00000000',
    );

    expect($promoCode->code)
        ->toStartWith('ITC-')
        ->and($promoCode->package_definition_id)->toBe($definition->id)
        ->and($promoCode->reduced_minimum_amount)->toBe('100.00000000');
})->with([
    'privilege' => [PackageTypeEnum::PRIVILEGE],
    'vip' => [PackageTypeEnum::VIP],
]);

it('creates staking package with inactive staking package definition', function () {
    $user = User::factory()->create();

    PackageDefinition::query()
        ->where('slug', PackageTypeEnum::STAKING->value)
        ->update(['is_active' => false]);

    $package = CreateStakingPackageAction::make()->run($user->id, 100.00);

    expect($package->type)->toBe(PackageTypeEnum::STAKING)
        ->and($package->packageDefinition)->not->toBeNull()
        ->and($package->packageDefinition->type)->toBe(PackageTypeEnum::STAKING)
        ->and($package->packageDefinition->is_active)->toBeFalse();
});

it('rejects admin promo code generation when reduced threshold is not below package minimum', function () {
    $this->withoutMiddleware();

    $definition = activeStandardPackageDefinition('2510.00000000');

    $response = $this->from('/itcapitalmoonshineadminpanel/resource/promo-code-resource/index-page')
        ->post(route('admin.promo-codes.generate'), [
            'package_definition_id' => $definition->id,
            'reduced_minimum_amount' => '3000',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('messageType', 'error')
        ->assertJsonPath('message', 'Сумма сниженного порога должна быть меньше минимальной суммы тарифа (2510.00).')
        ->assertJsonMissingPath('redirect');

    expect(PromoCode::query()->exists())->toBeFalse();
});

it('redeems a valid promo code once during package purchase', function () {
    $user = User::factory()->create();
    fundMainBalance($user);
    $definition = activeStandardPackageDefinition('50.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'PROMO50',
        'package_definition_id' => $definition->id,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'PROMO50')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect($promoCode->usages()->where('user_id', $user->id)->exists())->toBeTrue();

    $transaction = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and((string) $transaction->amount)->toBe('50.00')
        ->and(ItcPackage::query()->where('uuid', $transaction->uuid)->exists())->toBeTrue();
});

it('allows package purchase without a promo code', function () {
    $user = User::factory()->create();
    fundMainBalance($user);
    $definition = activeStandardPackageDefinition();

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '250')
        ->set('promoCode', '')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $transaction = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and((string) $transaction->amount)->toBe('250.00')
        ->and(ItcPackage::query()->where('uuid', $transaction->uuid)->exists())->toBeTrue();
});

it('allows different users to use the same promo code', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    fundMainBalance($firstUser);
    fundMainBalance($secondUser);
    $definition = activeStandardPackageDefinition('50.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'MULTI50',
        'package_definition_id' => $definition->id,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($firstUser);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'MULTI50')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $this->actingAs($secondUser);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'MULTI50')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect($promoCode->usages()->count())->toBe(2)
        ->and($promoCode->usages()->where('user_id', $firstUser->id)->exists())->toBeTrue()
        ->and($promoCode->usages()->where('user_id', $secondUser->id)->exists())->toBeTrue();
});

it('rejects same user reusing a promo code for the same package type', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '200.00000000');
    $definition = activeStandardPackageDefinition('50.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'ONCE50',
        'package_definition_id' => $definition->id,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'ONCE50')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasNoErrors();

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '60')
        ->set('promoCode', 'ONCE50')
        ->call('applyPromoCode')
        ->assertHasErrors(['promoCode']);

    expect($promoCode->usages()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Transaction::query()
            ->where('user_id', $user->id)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->count())->toBe(1);
});

it('rejects promo code for a different package type', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '300.00000000');
    $definition = activeStandardPackageDefinition('50.00000000');
    $vipDefinition = activePackageDefinition(PackageTypeEnum::VIP, '5000.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'SAME50',
        'package_definition_id' => $vipDefinition->id,
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'SAME50')
        ->call('applyPromoCode')
        ->assertHasErrors(['promoCode']);

    expect($promoCode->usages()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects a standard promo code for privilege package purchase', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '3000.00000000');
    $definition = activePackageDefinition(PackageTypeEnum::PRIVILEGE, '2500.00000000');
    $standardDefinition = activeStandardPackageDefinition('250.00000000');

    PromoCode::factory()->create([
        'code' => 'STD100',
        'package_definition_id' => $standardDefinition->id,
        'reduced_minimum_amount' => '100.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '2500')
        ->set('promoCode', 'STD100')
        ->call('applyPromoCode')
        ->assertHasErrors(['promoCode']);
});

it('accepts a vip promo code for vip package purchase', function () {
    $user = User::factory()->create();
    fundMainBalance($user, '6000.00000000');
    $definition = activePackageDefinition(PackageTypeEnum::VIP, '5000.00000000');

    $promoCode = PromoCode::factory()->create([
        'code' => 'VIP100',
        'package_definition_id' => $definition->id,
        'reduced_minimum_amount' => '100.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '5000')
        ->set('promoCode', 'VIP100')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect($promoCode->usages()->where('user_id', $user->id)->exists())->toBeTrue();

    $package = ItcPackage::query()
        ->where('package_definition_id', $definition->id)
        ->first();

    expect($package)->not->toBeNull()
        ->and($package->type)->toBe(PackageTypeEnum::VIP);
});

it('rejects invalid and mismatched promo codes without creating a package', function (string $code, ?PackageTypeEnum $mismatchedPackageType) {
    $user = User::factory()->create();
    fundMainBalance($user);
    $definition = activeStandardPackageDefinition('50.00000000');

    if ($mismatchedPackageType !== null) {
        $mismatchedDefinition = activePackageDefinition($mismatchedPackageType, '5000.00000000');

        PromoCode::factory()->create([
            'code' => $code,
            'package_definition_id' => $mismatchedDefinition->id,
            'reduced_minimum_amount' => '50.00000000',
        ]);
    }

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', $code)
        ->call('applyPromoCode')
        ->assertHasErrors(['promoCode']);

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->exists())->toBeFalse();
})->with([
    'invalid code' => ['UNKNOWN50', null],
    'mismatched package type' => ['VIP50', PackageTypeEnum::VIP],
]);

it('rejects package purchase below the promo reduced threshold', function () {
    $user = User::factory()->create();
    fundMainBalance($user);
    $definition = activeStandardPackageDefinition('250.00000000');

    PromoCode::factory()->create([
        'code' => 'MIN75',
        'package_definition_id' => $definition->id,
        'reduced_minimum_amount' => '75.00000000',
    ]);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '50')
        ->set('promoCode', 'MIN75')
        ->call('applyPromoCode')
        ->call('buyPackage')
        ->assertHasErrors(['amount']);

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->exists())->toBeFalse();
});

it('soft deletes a promo code from the admin panel', function () {
    $this->withoutMiddleware();

    $definition = activeStandardPackageDefinition('250.00000000');
    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create();

    $response = $this->from(PROMO_ADMIN_INDEX_REFERER)
        ->delete(route('admin.promo-codes.delete', ['promoCode' => $promoCode->id]));

    $response
        ->assertOk()
        ->assertJsonPath('messageType', 'success');

    $this->assertSoftDeleted('promo_codes', ['id' => $promoCode->id]);
    expect(PromoCode::query()->whereKey($promoCode->id)->exists())->toBeFalse();
});

it('restores a soft-deleted promo code from the admin panel', function () {
    $this->withoutMiddleware();

    $definition = activeStandardPackageDefinition('250.00000000');
    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create();
    $promoCode->delete();

    $response = $this->from(PROMO_ADMIN_INDEX_REFERER)
        ->post(route('admin.promo-codes.restore', ['promoCode' => $promoCode->id]));

    $response
        ->assertOk()
        ->assertJsonPath('messageType', 'success');

    $this->assertNotSoftDeleted('promo_codes', ['id' => $promoCode->id]);
    expect(PromoCode::query()->whereKey($promoCode->id)->exists())->toBeTrue();
});

it('updates a promo code amount from the admin panel', function () {
    $this->withoutMiddleware();

    $definition = activeStandardPackageDefinition('250.00000000');
    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create([
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $response = $this->from(PROMO_ADMIN_INDEX_REFERER)
        ->post(route('admin.promo-codes.update-amount', ['promoCode' => $promoCode->id]), [
            'reduced_minimum_amount' => '75',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('messageType', 'success');

    expect($promoCode->refresh()->reduced_minimum_amount)->toBe('75.00000000');
});

it('rejects a promo code amount update at or above the package minimum', function () {
    $this->withoutMiddleware();

    $definition = activeStandardPackageDefinition('250.00000000');
    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create([
        'reduced_minimum_amount' => '50.00000000',
    ]);

    $response = $this->from(PROMO_ADMIN_INDEX_REFERER)
        ->post(route('admin.promo-codes.update-amount', ['promoCode' => $promoCode->id]), [
            'reduced_minimum_amount' => '300',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('messageType', 'error')
        ->assertJsonMissingPath('redirect');

    expect($promoCode->refresh()->reduced_minimum_amount)->toBe('50.00000000');
});

it('treats a soft-deleted promo code as invalid during purchase validation', function () {
    $user = User::factory()->create();
    $definition = activeStandardPackageDefinition('250.00000000');

    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create([
        'code' => 'DELME50',
        'reduced_minimum_amount' => '50.00000000',
    ]);
    $promoCode->delete();

    $result = app(PackagePromoCodeService::class)
        ->validateForPurchase($user, $definition, '50', 'DELME50');

    expect($result->errorCode)->toBe(PackagePromoCodeService::ERROR_INVALID)
        ->and($result->promoCode)->toBeNull();
});

it('cannot redeem a soft-deleted promo code', function () {
    $user = User::factory()->create();
    $definition = activeStandardPackageDefinition('250.00000000');

    $promoCode = PromoCode::factory()->forPackageDefinition($definition)->create([
        'reduced_minimum_amount' => '50.00000000',
    ]);
    $promoCode->delete();

    expect(fn () => app(PackagePromoCodeService::class)->redeem($promoCode, $user, $definition))
        ->toThrow(RuntimeException::class);

    expect($promoCode->usages()->count())->toBe(0);
});
