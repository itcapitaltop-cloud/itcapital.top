<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\PackageDefinition;

use App\Models\BusinessActivity;
use App\Models\Package\PackageDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class PackageDefinitionIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable()->showOnExport(),
            Text::make('Slug', 'slug')->sortable()->showOnExport(),
            Text::make('Категория', 'type', formatted: fn (PackageDefinition $item): string => $item->type->getName())->sortable()->showOnExport(),
            Number::make('Процент прибыли', 'default_profit_percent', formatted: fn (PackageDefinition $item): string => $item->default_profit_percent . '%')->showOnExport(),
            Number::make('Минимальная сумма', 'min_start_amount', formatted: fn (PackageDefinition $item): string => $item->min_start_amount)->showOnExport(),
            Number::make('Срок, мес.', 'duration_months')->showOnExport(),
            Text::make('Статус', formatted: fn (PackageDefinition $item): string => $this->statusLabel($item))->showOnExport(),
            Number::make('Сортировка', 'sort_order')->sortable()->showOnExport(),
            Date::make('В архиве с', 'deleted_at')->format('d.m.Y H:i')->sortable()->showOnExport(),
            Td::make('Действия')
                ->fields(function (Td $field): array {
                    $item = $field->getData();

                    if (! $item instanceof PackageDefinition || ! $item->trashed()) {
                        return [Text::make('', formatted: fn (): string => '')];
                    }

                    return [
                        ActionButton::make('Восстановить')
                            ->method(
                                'restorePackageDefinition',
                                params: fn (): array => ['resourceItem' => $item->id]
                            )
                            ->success(),
                    ];
                }),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        $eventSearch = trim((string) request('package_definition_activity_event', ''));
        $activeTab = $this->resolveActiveTab($eventSearch);

        return [
            Tabs::make([
                Tab::make('Данные', [
                    ...parent::mainLayer(),
                ])->active(fn (): bool => $activeTab === 'data'),
                Tab::make('Журнал', $this->journalComponents($eventSearch))
                    ->active(fn (): bool => $activeTab === 'logs'),
            ]),
            FlexibleRender::make(fn (): string => $this->tabUrlSyncScript()),
        ];
    }

    /**
     * Tab switching is client-side (Alpine) and does not touch the URL, so once tab=logs
     * lands in the query string (via journal pagination or search) a reload would reopen
     * the journal even after the user switched back to "Данные". Mirror the active tab
     * into the URL on every tab click and drop the journal-only params with it.
     */
    private function tabUrlSyncScript(): string
    {
        return <<<'HTML'
            <script>
                document.addEventListener('click', (event) => {
                    const button = event.target.closest('.tabs-button');

                    if (!button) {
                        return;
                    }

                    const url = new URL(window.location.href);

                    if (button.textContent.trim() === 'Журнал') {
                        url.searchParams.set('tab', 'logs');
                    } else {
                        url.searchParams.delete('tab');
                        url.searchParams.delete('package_definition_activity_page');
                        url.searchParams.delete('package_definition_activity_event');
                    }

                    window.history.replaceState(null, '', url);
                });
            </script>
        HTML;
    }

    /**
     * Keep the "Журнал" tab open after paginating or searching its table: those links
     * carry the journal params but not necessarily tab=logs, so the tab would otherwise
     * reset to "Данные" on reload.
     */
    private function resolveActiveTab(string $eventSearch): string
    {
        $onJournal = request()->has('package_definition_activity_page') || $eventSearch !== '';

        return $onJournal ? 'logs' : request('tab', 'data');
    }

    /**
     * @return list<MoonShineComponent>
     */
    private function journalComponents(string $eventSearch): array
    {
        $activities = BusinessActivity::query()
            ->where('subject_type', PackageDefinition::class)
            ->with('causer')
            ->when($eventSearch !== '', fn (Builder $query): Builder => $this->applyActivitySearch($query, $eventSearch))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'package_definition_activity_page')
            ->appends(array_filter([
                'tab' => 'logs',
                'package_definition_activity_event' => $eventSearch !== '' ? $eventSearch : null,
            ]));

        $packageNames = $this->packageNamesBySubjectId($activities->getCollection());

        $rows = collect($activities->items())
            ->map(fn (BusinessActivity $activity): array => [
                'package' => $this->activityPackageName($activity, $packageNames),
                'event' => $activity->description,
                'admin_login' => $this->activityAdminLogin($activity),
                'old_values' => $this->formatActivityValues((array) $activity->getExtraProperty('old_values', [])),
                'new_values' => $this->formatActivityValues((array) $activity->getExtraProperty('new_values', [])),
                'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
            ])
            ->all();

        return [
            FlexibleRender::make(fn (): string => $this->activitySearchForm($eventSearch)),
            TableBuilder::make()
                ->withNotFound()
                ->fields([
                    Text::make('Тариф', 'package'),
                    Text::make('Событие', 'event'),
                    Text::make('Администратор', 'admin_login'),
                    Text::make('Старые значения', 'old_values'),
                    Text::make('Новые значения', 'new_values'),
                    Text::make('Дата', 'date'),
                ])
                ->items($rows),
            ...$this->journalPagination($activities),
        ];
    }

    /**
     * Render the journal pagination ourselves, inside the journal tab, through the shared
     * admin pagination view (resources/views/vendor/moonshine/ui/pagination.blade.php).
     * Passing a plain array to the table (instead of a paginator) stops MoonShine from
     * rendering its own paginator block, which otherwise leaked under the "Данные" tab.
     *
     * @param LengthAwarePaginator $paginator
     * @return list<MoonShineComponent>
     */
    private function journalPagination(LengthAwarePaginator $paginator): array
    {
        return [
            FlexibleRender::make(fn (): string => $paginator->links('moonshine::ui.pagination', ['async' => false])->toHtml()),
        ];
    }

    /**
     * Filter the journal by a single term that matches the event description, the package
     * name stored on the entry, or the current name of the related package (incl. archived).
     *
     * @param Builder<BusinessActivity> $query
     * @return Builder<BusinessActivity>
     */
    private function applyActivitySearch(Builder $query, string $search): Builder
    {
        $like = '%' . mb_strtolower($search) . '%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner
                ->whereRaw('lower(description) like ?', [$like])
                ->orWhereRaw("lower(properties->>'package_name') like ?", [$like])
                ->orWhereIn('subject_id', PackageDefinition::withTrashed()
                    ->whereRaw('lower(name) like ?', [$like])
                    ->select('id'));
        });
    }

    /**
     * Resolve package names for the activities currently on the page in a single query,
     * used as a fallback for older log entries that predate the stored package name.
     *
     * @param \Illuminate\Support\Collection<int, BusinessActivity> $activities
     * @return array<int, string>
     */
    private function packageNamesBySubjectId(\Illuminate\Support\Collection $activities): array
    {
        $ids = $activities
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->all();

        if ($ids === []) {
            return [];
        }

        return PackageDefinition::withTrashed()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param array<int, string> $packageNames
     */
    private function activityPackageName(BusinessActivity $activity, array $packageNames): string
    {
        $storedName = $activity->getExtraProperty('package_name');

        if (is_string($storedName) && $storedName !== '') {
            return $storedName;
        }

        $resolvedName = $packageNames[$activity->subject_id] ?? null;

        if (is_string($resolvedName) && $resolvedName !== '') {
            return $resolvedName;
        }

        $slug = $activity->getExtraProperty('package_slug');

        return is_string($slug) && $slug !== '' ? $slug : '—';
    }

    private function statusLabel(PackageDefinition $item): string
    {
        if ($item->trashed()) {
            return 'Архив';
        }

        return $item->is_active ? 'Доступен в ЛК' : 'Только в админке';
    }

    /**
     * @param array<string, mixed> $values
     */
    private function formatActivityValues(array $values): string
    {
        return collect($values)
            ->map(fn (mixed $value, string|int $key): string => $this->formatActivityValue($value))
            ->implode("\n");
    }

    private function formatActivityValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Да' : 'Нет';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function activityAdminLogin(BusinessActivity $activity): string
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            return $causer->username;
        }

        return (string) ($activity->getExtraProperty('admin_login') ?? '');
    }

    private function activitySearchForm(string $eventSearch): string
    {
        $action = e(request()->url());
        $eventSearch = e($eventSearch);

        return <<<HTML
            <form method="GET" action="{$action}" style="margin-bottom:16px;">
                <input type="hidden" name="tab" value="logs">
                <span style="display:block;margin-bottom:4px;">Поиск по событию или названию тарифа</span>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input class="form-input" style="height:42px;min-width:280px;margin-bottom:0;" type="text" name="package_definition_activity_event" value="{$eventSearch}" placeholder="Например: Изменение тарифа или Бизнес Старт">
                    <button class="btn btn-primary" style="height:42px;display:inline-flex;align-items:center;" type="submit">Найти</button>
                    <a class="btn" style="height:42px;display:inline-flex;align-items:center;" href="{$action}?tab=logs">Сбросить</a>
                </div>
            </form>
        HTML;
    }
}
