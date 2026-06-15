<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\Models\Transaction;
use App\Models\User;
use App\MoonShine\Resources\PackageDefinitionResource;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * Packages snapshot the definition's profit %, duration and work_to at purchase.
 * Editing the definition afterwards in the admin "Настройки пакетов" section MUST
 * propagate both the new profit percent and the new term to every package bought from
 * that definition: duration_months is updated and work_to is recomputed from the
 * package's own created_at + the new term.
 */
it('propagates the new profit percent and term to an already purchased package when its definition is edited', function () {
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

    // Admin later raises the rate, shortens the term, renames the package — through the
    // admin resource, which is what cascades the profit percent.
    $resource = new PackageDefinitionResource();
    $reflection = new ReflectionMethod($resource, 'beforeUpdating');
    $reflection->setAccessible(true);
    $reflection->invoke($resource, $definition);

    $definition->update([
        'default_profit_percent' => '12.00',
        'duration_months' => 9,
        'name' => 'Re-tuned Standard',
    ]);

    $afterUpdated = new ReflectionMethod($resource, 'afterUpdated');
    $afterUpdated->setAccessible(true);
    $afterUpdated->invoke($resource, $definition);

    $package->refresh();

    // The new rate and term both reach the bought package; work_to is recomputed from
    // its own created_at (2026-05-25) + the new 9-month term.
    expect($package->month_profit_percent)->toBe('12.00')
        ->and($package->duration_months)->toBe(9)
        ->and($package->work_to->toDateString())->toBe('2027-02-25');

    Carbon::setTestNow();
});

it('does not overwrite a package whose profit percent was manually overridden', function () {
    $definition = PackageDefinition::query()
        ->where('type', PackageTypeEnum::STANDARD)
        ->firstOrFail();

    $definition->update([
        'default_profit_percent' => '7.00',
        'is_active' => true,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => User::factory()->create()->id,
        'amount' => '1000.00',
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);

    // Admin pinned this package's rate manually — the cascade must leave it alone.
    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        'package_definition_id' => $definition->id,
        'month_profit_percent' => '9.50',
        'profit_percent_overridden' => true,
    ]);

    $resource = new PackageDefinitionResource();
    $before = new ReflectionMethod($resource, 'beforeUpdating');
    $before->invoke($resource, $definition);

    $definition->update(['default_profit_percent' => '3.00']);

    $after = new ReflectionMethod($resource, 'afterUpdated');
    $after->invoke($resource, $definition);

    expect($package->fresh()->month_profit_percent)->toBe('9.50');
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
