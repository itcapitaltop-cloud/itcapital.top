<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * The whole feature rests on snapshot-at-purchase semantics: a package copies the
 * definition's profit %, duration and work_to once, at purchase. Editing the
 * definition afterwards (or in the admin "Packages" section) MUST NOT retroactively
 * change anyone's already-bought package or its accrual base. This is the property
 * that protects historical balances from a single admin edit.
 */
it('does not mutate an already purchased package when its definition is later edited', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $definition = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->firstOrFail();

    $definition->update([
        'default_profit_percent' => '7.00',
        'min_start_amount' => '150.00000000',
        'duration_months' => 3,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '1000.00',
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);
    $this->actingAs($user);

    Livewire::test(ItcPackages::class)
        ->set('selectedPackageDefinitionId', $definition->id)
        ->set('amount', '200')
        ->call('buyPackage')
        ->assertHasNoErrors();

    $package = ItcPackage::query()->where('package_definition_id', $definition->id)->firstOrFail();

    expect($package->month_profit_percent)->toBe('7.00')
        ->and($package->duration_months)->toBe(3)
        ->and($package->work_to->toDateString())->toBe('2026-08-25');

    // Admin later raises the rate, shortens the term, renames the package.
    $definition->update([
        'default_profit_percent' => '12.00',
        'duration_months' => 9,
        'name' => 'Re-tuned Standard',
    ]);

    $package->refresh();

    // The bought package is frozen — accrual base and term are unchanged.
    expect($package->month_profit_percent)->toBe('7.00')
        ->and($package->duration_months)->toBe(3)
        ->and($package->work_to->toDateString())->toBe('2026-08-25');

    Carbon::setTestNow();
});

it('keeps legacy packages without a definition on their own stored rate', function () {
    // Packages created before the feature have package_definition_id = NULL and
    // must keep accruing from their own stored month_profit_percent, fully
    // independent of any PackageDefinition row.
    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '1000.00',
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);

    $legacyPackage = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        'package_definition_id' => null,
        'month_profit_percent' => '8.20',
    ]);

    // Move every standard definition to a different rate.
    PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->update(['default_profit_percent' => '3.33', 'is_active' => true]);

    expect($legacyPackage->fresh()->package_definition_id)->toBeNull()
        ->and($legacyPackage->fresh()->month_profit_percent)->toBe('8.20');
});
