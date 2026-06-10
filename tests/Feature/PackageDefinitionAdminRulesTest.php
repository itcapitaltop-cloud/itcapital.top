<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use App\MoonShine\Resources\PackageDefinitionResource;

/**
 * Guards the admin-side safety rules for the "Packages" section:
 *  - an empty sort_order must never reach the NOT NULL column as NULL;
 *  - purchasable packages must run at least one month so they don't expire instantly.
 */
it('coerces an empty sort order to zero instead of failing on the NOT NULL column', function () {
    $definition = PackageDefinition::factory()->create(['sort_order' => null]);

    expect($definition->fresh()->sort_order)->toBe(0);
});

it('requires at least one month of duration for purchasable package types', function (string $type) {
    request()->merge(['type' => $type]);

    $item = new PackageDefinition(['type' => PackageTypeEnum::from($type)]);
    $rules = (new PackageDefinitionResource())->rules($item);

    expect($rules['duration_months'])
        ->toContain('required')
        ->toContain('integer')
        ->toContain('min:1')
        ->toContain('max:120');
})->with([
    PackageTypeEnum::STANDARD->value,
    PackageTypeEnum::PRIVILEGE->value,
    PackageTypeEnum::VIP->value,
]);

it('requires duration for present and staking definitions but allows zero months', function (string $type) {
    request()->merge(['type' => $type]);

    $item = new PackageDefinition(['type' => PackageTypeEnum::from($type)]);
    $rules = (new PackageDefinitionResource())->rules($item);

    expect($rules['duration_months'])
        ->toContain('required')
        ->toContain('min:0')
        ->not->toContain('min:1')
        ->not->toContain('nullable');
})->with([
    PackageTypeEnum::PRESENT->value,
    PackageTypeEnum::STAKING->value,
]);
