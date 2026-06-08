<?php

use App\Actions\PromoCodes\GeneratePromoCodeAction;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Livewire\Index\Main as IndexMain;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\PackageDefinitionResolver;
use App\Services\Package\Staking\StakingPurchaseService;
use Carbon\Carbon;
use Livewire\Livewire;

function fundPackageDefinitionTestBalance(User $user, string $amount = '1000.00000000'): void
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

it('resolves only active package definitions', function () {
    PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->update(['is_active' => false]);

    app(PackageDefinitionResolver::class)->resolve(PackageTypeEnum::STANDARD);
})->throws(RuntimeException::class, 'Active package definition for [standard] is not configured.');

it('snapshots configured standard package defaults during account purchase', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $definition = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->firstOrFail();

    $definition->update([
        'name' => 'Configured Standard',
        'default_profit_percent' => '9.75',
        'min_start_amount' => '150.00000000',
        'duration_months' => 4,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    fundPackageDefinitionTestBalance($user);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '150')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $package = ItcPackage::query()
        ->where('package_definition_id', $definition->id)
        ->firstOrFail();

    expect($package->type)->toBe(PackageTypeEnum::STANDARD)
        ->and($package->month_profit_percent)->toBe('9.75')
        ->and($package->duration_months)->toBe(4)
        ->and($package->work_to->toDateString())->toBe('2026-09-25');
});

it('requires an explicit package selection before applying promo code or buying', function () {
    $user = User::factory()->create();
    fundPackageDefinitionTestBalance($user);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('promoCode', 'PROMO')
        ->call('applyPromoCode')
        ->assertHasErrors(['selectedPackageDefinitionId']);

    Livewire::test(ItcPackages::class)
        ->set('amount', '250')
        ->call('buyPackage')
        ->assertHasErrors(['selectedPackageDefinitionId' => 'required']);
});

it('shows only active account package definitions in purchase selector', function () {
    $activeDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Visible Account Package',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $inactiveDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Hidden Account Package',
        'is_active' => false,
        'sort_order' => 11,
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->assertSee($activeDefinition->name)
        ->assertDontSee($inactiveDefinition->name);
});

it('shows only active standard package definitions on index page', function () {
    $activeDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Visible Index Package',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $inactiveDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Hidden Index Package',
        'is_active' => false,
        'sort_order' => 11,
    ]);

    $stakingDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STAKING,
        'name' => 'Hidden Staking Package',
        'is_active' => true,
        'sort_order' => 12,
    ]);

    Livewire::test(IndexMain::class)
        ->assertSee($activeDefinition->name)
        ->assertDontSee($inactiveDefinition->name)
        ->assertDontSee($stakingDefinition->name);
});

it('buys the explicitly selected active package definition', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $archivedStandard = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->orderBy('id')
        ->firstOrFail();

    $archivedStandard->update(['is_active' => false]);

    $selectedDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Selected Growth Package',
        'default_profit_percent' => '11.50',
        'min_start_amount' => '300.00000000',
        'duration_months' => 7,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $otherDefinition = PackageDefinition::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Other Active Package',
        'default_profit_percent' => '4.00',
        'min_start_amount' => '100.00000000',
        'duration_months' => 2,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $user = User::factory()->create();
    fundPackageDefinitionTestBalance($user);

    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $selectedDefinition->id)
        ->set('amount', '300')
        ->call('buyPackage')
        ->assertHasNoErrors();

    expect(ItcPackage::query()->where('package_definition_id', $selectedDefinition->id)->exists())->toBeTrue()
        ->and(ItcPackage::query()->where('package_definition_id', $archivedStandard->id)->exists())->toBeFalse()
        ->and(ItcPackage::query()->where('package_definition_id', $otherDefinition->id)->exists())->toBeFalse();
});

it('allows multiple admin-defined package slugs for the same regular category', function () {
    $definition = PackageDefinition::factory()->create([
        'slug' => 'business-start',
        'type' => PackageTypeEnum::STANDARD,
        'name' => 'Business Start',
        'default_profit_percent' => '7.25',
        'min_start_amount' => '500.00000000',
        'duration_months' => 3,
        'is_active' => true,
        'sort_order' => 100,
    ]);

    expect($definition->slug)->toBe('business-start')
        ->and($definition->type)->toBe(PackageTypeEnum::STANDARD)
        ->and(PackageDefinition::query()->where('type', PackageTypeEnum::STANDARD)->count())->toBeGreaterThan(1);
});

it('uses configured minimum when generating promo codes', function () {
    PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->update(['min_start_amount' => '300.00000000']);

    $definition = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->active()
        ->orderBy('sort_order')
        ->orderBy('id')
        ->firstOrFail();

    $promoCode = GeneratePromoCodeAction::make()->run(
        $definition,
        '299.00000000',
    );

    expect($promoCode->reduced_minimum_amount)->toBe('299.00000000');

    GeneratePromoCodeAction::make()->run(
        $definition,
        '300.00000000',
    );
})->throws(InvalidArgumentException::class, 'Reduced minimum amount must be lower than the default package minimum.');

it('snapshots configured staking package defaults', function () {
    $definition = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STAKING)
        ->firstOrFail();

    $definition->update([
        'default_profit_percent' => '3.50',
        'duration_months' => 0,
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 1000);

    expect($package->package_definition_id)->toBe($definition->id)
        ->and($package->month_profit_percent)->toBe('3.50')
        ->and($package->duration_months)->toBe(0)
        ->and($package->type)->toBe(PackageTypeEnum::STAKING);
});

afterEach(function (): void {
    Carbon::setTestNow();
});
