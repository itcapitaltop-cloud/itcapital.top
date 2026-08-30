<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\MoonShine\Forms\ItcPackageTariffField;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Select;

it('renders a package type enum field for a legacy package', function (): void {
    $package = ItcPackage::factory()->create([
        'package_definition_id' => null,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    $field = ItcPackageTariffField::make($package);

    expect($field)->toBeInstanceOf(Enum::class)
        ->and($field->column())->toBe('type')
        ->and($field->toValue())->toBe(PackageTypeEnum::STANDARD);
});

it('renders a tariff select prefilled with the current definition for a definition-based package', function (): void {
    $standard = PackageDefinition::query()->where('slug', PackageTypeEnum::STANDARD->value)->sole();
    $privilege = PackageDefinition::query()->where('slug', PackageTypeEnum::PRIVILEGE->value)->sole();
    $adminOnly = PackageDefinition::factory()->inactive()->create([
        'slug' => 'legacy-tariff',
        'name' => 'Legacy',
    ]);

    $package = ItcPackage::factory()->create([
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    $field = ItcPackageTariffField::make($package);

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->column())->toBe('package_definition_id')
        ->and($field->toValue())->toBe($standard->id)
        ->and($field->values())->toHaveKey(ItcPackageTariffField::ARCHIVE_VALUE)
        ->and($field->values())->toMatchArray([
            $standard->id => "{$standard->name} ({$standard->slug})",
            $privilege->id => "{$privilege->name} ({$privilege->slug})",
            $adminOnly->id => 'Legacy (legacy-tariff) — только в админке',
        ]);
});

it('keeps the current definition selectable even when it was soft deleted', function (): void {
    $retired = PackageDefinition::factory()->create([
        'slug' => 'retired-tariff',
        'name' => 'Retired',
    ]);

    $package = ItcPackage::factory()->create([
        'package_definition_id' => $retired->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    $retired->delete();

    $field = ItcPackageTariffField::make($package->refresh());

    expect($field->values())->toHaveKey($retired->id)
        ->and($field->toValue())->toBe($retired->id);
});

it('renders a read-only tariff preview for a staking package', function (): void {
    $staking = PackageDefinition::query()->where('slug', PackageTypeEnum::STAKING->value)->sole();

    $package = ItcPackage::factory()->create([
        'package_definition_id' => $staking->id,
        'type' => PackageTypeEnum::STAKING,
    ]);

    $field = ItcPackageTariffField::make($package);

    expect($field)->toBeInstanceOf(Preview::class)
        ->and($field->column())->not->toBe('type')
        ->and($field->column())->not->toBe('package_definition_id');
});

it('preselects "keep archived" for an archived package so an unrelated save cannot un-archive it', function (): void {
    $standard = PackageDefinition::query()->where('slug', PackageTypeEnum::STANDARD->value)->sole();

    $package = ItcPackage::factory()->create([
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::ARCHIVE,
    ]);

    $field = ItcPackageTariffField::make($package);

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->column())->toBe('package_definition_id')
        ->and($field->toValue())->toBe('')
        ->and($field->values())->toHaveKey('')
        ->and($field->values()[''])->toBe('Оставить в архиве')
        ->and($field->values())->toHaveKey($standard->id);
});
