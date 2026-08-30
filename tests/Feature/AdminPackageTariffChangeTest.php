<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Http\Controllers\AdminController;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use App\Models\Transaction;
use App\Models\User;
use App\MoonShine\Forms\ItcPackageTariffField;
use App\Services\ActivityLog\ActivityFeedService;
use MoonShine\Enums\ToastType;
use MoonShine\MoonShineRequest;

/**
 * The canonical tariff rows are seeded by the fill_package_definition migration,
 * so tests reuse them instead of recreating colliding slugs.
 */
function seededPackageDefinition(PackageTypeEnum $type): PackageDefinition
{
    return PackageDefinition::query()->where('slug', $type->value)->sole();
}

/**
 * Create a package plus its matching transaction for $user.
 *
 * @param array<string, mixed> $attributes
 */
function makeAdminEditablePackage(User $user, array $attributes = []): ItcPackage
{
    $package = ItcPackage::factory()->create($attributes);

    Transaction::factory()->create([
        'uuid' => $package->uuid,
        'user_id' => $user->id,
        'amount' => '1000.00000000',
    ]);

    return $package->refresh();
}

/**
 * @param array<string, mixed> $payload
 */
function updateItcPackageAsAdmin(ItcPackage $package, array $payload): mixed
{
    $request = MoonShineRequest::create(
        '/itcapitalmoonshineadminpanel/itc-packages/' . $package->uuid,
        'POST',
        [
            'amount' => '1000',
            'created_at' => $package->created_at->toDateString(),
            'profit_percent' => (string) $package->month_profit_percent,
            ...$payload,
        ],
    );

    app()->instance('request', $request);

    return (new AdminController())->updateItcPackage($package->uuid, $request);
}

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->owner = User::factory()->create();
    $this->actingAs($this->admin);
});

it('moves a definition-based package to the submitted tariff and syncs its type', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);
    $privilege = seededPackageDefinition(PackageTypeEnum::PRIVILEGE);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.20',
        'work_to' => now()->addMonths(6),
    ]);
    $originalWorkTo = $package->work_to;

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $privilege->id]);

    $package->refresh();

    expect($package->package_definition_id)->toBe($privilege->id)
        ->and($package->type)->toBe(PackageTypeEnum::PRIVILEGE)
        ->and((float) $package->month_profit_percent)->toBe(8.20)
        ->and($package->work_to->toDateTimeString())->toBe($originalWorkTo->toDateTimeString());
});

it('moves a definition-based package to a custom tariff whose slug is not a package type', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);
    $custom = PackageDefinition::factory()->create([
        'slug' => 'privilege-plus',
        'name' => 'Privilege Plus',
        'type' => PackageTypeEnum::PRIVILEGE,
    ]);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $custom->id]);

    $package->refresh();

    expect($package->package_definition_id)->toBe($custom->id)
        ->and($package->packageDefinition->slug)->toBe('privilege-plus')
        ->and($package->type)->toBe(PackageTypeEnum::PRIVILEGE);
});

it('changes the type of a legacy package and leaves it without a definition', function (): void {
    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => null,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    updateItcPackageAsAdmin($package, ['type' => PackageTypeEnum::PRIVILEGE->value]);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::PRIVILEGE)
        ->and($package->package_definition_id)->toBeNull();
});

it('ignores tariff input submitted for a staking package', function (): void {
    $staking = seededPackageDefinition(PackageTypeEnum::STAKING);
    $privilege = seededPackageDefinition(PackageTypeEnum::PRIVILEGE);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $staking->id,
        'type' => PackageTypeEnum::STAKING,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $privilege->id]);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::STAKING)
        ->and($package->package_definition_id)->toBe($staking->id);
});

it('archives an active package without returning its body to the balance', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);
    $balanceBefore = Transaction::query()->where('user_id', $this->owner->id)->count();

    updateItcPackageAsAdmin($package, ['package_definition_id' => ItcPackageTariffField::ARCHIVE_VALUE]);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::ARCHIVE)
        ->and($package->package_definition_id)->toBe($standard->id)
        ->and(Transaction::query()->where('user_id', $this->owner->id)->count())->toBe($balanceBefore);
});

it('keeps an archived package archived when the "keep archived" option is submitted', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::ARCHIVE,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => '']);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::ARCHIVE)
        ->and($package->package_definition_id)->toBe($standard->id);
});

it('un-archives a package when the admin deliberately picks a tariff', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);
    $privilege = seededPackageDefinition(PackageTypeEnum::PRIVILEGE);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::ARCHIVE,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $privilege->id]);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::PRIVILEGE)
        ->and($package->package_definition_id)->toBe($privilege->id);
});

it('reports an error and changes nothing when the submitted definition does not exist', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.20',
    ]);

    $response = updateItcPackageAsAdmin($package, [
        'package_definition_id' => (string) (PackageDefinition::query()->max('id') + 999),
        'profit_percent' => '15.00',
    ]);

    $package->refresh();

    expect($package->package_definition_id)->toBe($standard->id)
        ->and($package->type)->toBe(PackageTypeEnum::STANDARD)
        ->and((float) $package->month_profit_percent)->toBe(8.20)
        ->and(json_decode($response->getContent(), true))->toMatchArray([
            'messageType' => ToastType::ERROR->value,
        ]);
});

it('changes nothing when a stale form posts a type for a definition-based package', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    updateItcPackageAsAdmin($package, ['type' => PackageTypeEnum::PRIVILEGE->value]);

    $package->refresh();

    expect($package->type)->toBe(PackageTypeEnum::STANDARD)
        ->and($package->package_definition_id)->toBe($standard->id);
});

it('writes a tariff change entry to the admin journal', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);
    $privilege = seededPackageDefinition(PackageTypeEnum::PRIVILEGE);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $privilege->id]);

    $activity = BusinessActivity::query()
        ->where('description', LogActionTypeEnum::UPDATE_ITC_PACKAGE_DEFINITION->value)
        ->sole();

    expect($activity->user_id)->toBe($this->owner->id)
        ->and($activity->getExtraProperty('old_values'))->toBe(['package_definition' => $standard->name])
        ->and($activity->getExtraProperty('new_values'))->toBe(['package_definition' => $privilege->name]);
});

it('shows the tariff change in the admin journal by name, without ids or a bogus amount', function (): void {
    $standard = seededPackageDefinition(PackageTypeEnum::STANDARD);
    $privilege = seededPackageDefinition(PackageTypeEnum::PRIVILEGE);

    $package = makeAdminEditablePackage($this->owner, [
        'package_definition_id' => $standard->id,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    updateItcPackageAsAdmin($package, ['package_definition_id' => (string) $privilege->id]);

    $row = collect(app(ActivityFeedService::class)->userDetailAdminFeed($this->owner->id)->items())
        ->firstWhere('action', 'Изменение тарифа пакета');

    expect($row)->not->toBeNull()
        ->and($row['old_values'])->toBe($standard->name)
        ->and($row['new_values'])->toBe($privilege->name)
        ->and($row['operation_amount'])->toBe('');
});
