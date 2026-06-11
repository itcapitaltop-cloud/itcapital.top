<?php

use App\Models\BusinessActivity;
use App\Models\Package\PackageDefinition;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionIndexPage;
use App\MoonShine\Resources\PackageDefinitionResource;
use Illuminate\Support\Collection;

/**
 * Makes the "Packages" admin journal intuitive: every audit entry must carry the
 * package name, and the global journal must still show a readable package name even
 * for older entries that predate the stored name.
 */
it('stores the package name on package definition audit log entries', function () {
    $definition = PackageDefinition::factory()->create(['name' => 'Бизнес Старт']);

    $resource = new PackageDefinitionResource();
    $afterCreated = (new ReflectionMethod($resource, 'afterCreated'));
    $afterCreated->setAccessible(true);
    $afterCreated->invoke($resource, $definition);

    $activity = BusinessActivity::query()
        ->where('subject_type', PackageDefinition::class)
        ->where('subject_id', $definition->id)
        ->latest('id')
        ->firstOrFail();

    expect($activity->getExtraProperty('package_name'))->toBe('Бизнес Старт');
});

it('resolves a readable package name in the journal for every kind of entry', function () {
    $page = new PackageDefinitionIndexPage();

    $named = PackageDefinition::factory()->create(['name' => 'Активный тариф']);
    $archived = PackageDefinition::factory()->create(['name' => 'Архивный тариф']);
    $archived->delete();

    // New entry: stored name wins.
    $modern = new BusinessActivity();
    $modern->subject_id = $named->id;
    $modern->properties = collect(['package_name' => 'Сохранённое имя', 'package_slug' => 'x']);

    // Legacy entry without stored name: resolved from the (soft-deleted) subject.
    $legacy = new BusinessActivity();
    $legacy->subject_id = $archived->id;
    $legacy->properties = collect(['package_slug' => 'arch-slug']);

    // Orphan entry: subject is gone, falls back to the slug.
    $orphan = new BusinessActivity();
    $orphan->subject_id = 999999;
    $orphan->properties = collect(['package_slug' => 'orphan-slug']);

    $namesBySubjectId = (new ReflectionMethod($page, 'packageNamesBySubjectId'));
    $namesBySubjectId->setAccessible(true);
    $map = $namesBySubjectId->invoke($page, new Collection([$modern, $legacy, $orphan]));

    $resolve = (new ReflectionMethod($page, 'activityPackageName'));
    $resolve->setAccessible(true);

    expect($resolve->invoke($page, $modern, $map))->toBe('Сохранённое имя')
        ->and($resolve->invoke($page, $legacy, $map))->toBe('Архивный тариф')
        ->and($resolve->invoke($page, $orphan, $map))->toBe('orphan-slug');
});

it('searches the journal by package name as well as by event', function () {
    $business = PackageDefinition::factory()->create(['name' => 'Бизнес Старт']);
    $vip = PackageDefinition::factory()->create(['name' => 'VIP Премиум']);

    activity('package_definitions')
        ->performedOn($business)
        ->withProperties(['package_name' => $business->name])
        ->log('Создание тарифа: Минимальная сумма');

    activity('package_definitions')
        ->performedOn($vip)
        ->withProperties(['package_name' => $vip->name])
        ->log('Изменение тарифа: Процент прибыли');

    $page = new PackageDefinitionIndexPage();
    $applySearch = (new ReflectionMethod($page, 'applyActivitySearch'));
    $applySearch->setAccessible(true);

    $search = function (string $term) use ($page, $applySearch): Collection {
        $query = BusinessActivity::query()->where('subject_type', PackageDefinition::class);

        return $applySearch->invoke($page, $query, $term)->pluck('description');
    };

    // By package name → only that package's entries.
    expect($search('Бизнес'))->toHaveCount(1)
        ->each->toContain('Создание тарифа');

    // By event text → still works across packages.
    expect($search('Изменение тарифа'))->toHaveCount(1)
        ->each->toContain('Процент прибыли');

    // By current package name even when the stored property is missing/renamed.
    $vip->update(['name' => 'VIP Платина']);
    expect($search('Платина'))->toHaveCount(1);
});

it('keeps the journal tab active when paginating or searching the log', function () {
    $page = new PackageDefinitionIndexPage();
    $resolveActiveTab = (new ReflectionMethod($page, 'resolveActiveTab'));
    $resolveActiveTab->setAccessible(true);

    // Default landing: data tab.
    request()->replace([]);
    expect($resolveActiveTab->invoke($page, ''))->toBe('data');

    // Explicitly chosen logs tab.
    request()->replace(['tab' => 'logs']);
    expect($resolveActiveTab->invoke($page, ''))->toBe('logs');

    // Journal pagination link (no tab param) must still keep the logs tab.
    request()->replace(['package_definition_activity_page' => '2']);
    expect($resolveActiveTab->invoke($page, ''))->toBe('logs');

    // Active journal search keeps the logs tab too.
    request()->replace([]);
    expect($resolveActiveTab->invoke($page, 'Бизнес'))->toBe('logs');
});

it('mirrors the active tab into the URL so a reload reopens the tab the user sees', function () {
    $page = new PackageDefinitionIndexPage();
    $script = (new ReflectionMethod($page, 'tabUrlSyncScript'));
    $script->setAccessible(true);

    $html = $script->invoke($page);

    // Clicking "Журнал" stores tab=logs; clicking back removes it together with
    // the journal-only params, so refreshing lands on the "Данные" tab again.
    expect($html)->toContain("searchParams.set('tab', 'logs')")
        ->toContain("searchParams.delete('tab')")
        ->toContain("searchParams.delete('package_definition_activity_page')")
        ->toContain("searchParams.delete('package_definition_activity_event')")
        ->toContain('history.replaceState');
});
