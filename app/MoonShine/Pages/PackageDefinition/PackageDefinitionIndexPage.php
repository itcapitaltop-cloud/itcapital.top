<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\PackageDefinition;

use App\Models\BusinessActivity;
use App\Models\Package\PackageDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $activeTab = request('tab', 'data');
        $eventSearch = trim((string) request('package_definition_activity_event', ''));
        $activities = BusinessActivity::query()
            ->where('subject_type', PackageDefinition::class)
            ->with('causer')
            ->when($eventSearch !== '', function (Builder $query) use ($eventSearch): void {
                $query->whereRaw('lower(description) like ?', ['%' . mb_strtolower($eventSearch) . '%']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'package_definition_activity_page');

        return [
            Tabs::make([
                Tab::make('Данные', [
                    ...parent::mainLayer(),
                ])->active(fn (): bool => $activeTab === 'data'),
                Tab::make('Журнал', [
                    FlexibleRender::make(fn (): string => $this->activitySearchForm($eventSearch)),
                    TableBuilder::make()
                        ->withNotFound()
                        ->fields([
                            Text::make('Событие', 'event'),
                            Text::make('Администратор', 'admin_login'),
                            Text::make('Старые значения', 'old_values'),
                            Text::make('Новые значения', 'new_values'),
                            Text::make('Дата', 'date'),
                        ])
                        ->items($activities->through(fn (BusinessActivity $activity): array => [
                            'event' => $activity->description,
                            'admin_login' => $this->activityAdminLogin($activity),
                            'old_values' => $this->formatActivityValues((array) $activity->getExtraProperty('old_values', [])),
                            'new_values' => $this->formatActivityValues((array) $activity->getExtraProperty('new_values', [])),
                            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
                        ])),
                ])->active(fn (): bool => $activeTab === 'logs'),
            ]),
        ];
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
                <span style="display:block;margin-bottom:4px;">Поиск по названию события</span>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input class="form-input" style="height:42px;min-width:280px;margin-bottom:0;" type="text" name="package_definition_activity_event" value="{$eventSearch}" placeholder="Например: Изменение тарифа">
                    <button class="btn btn-primary" style="height:42px;display:inline-flex;align-items:center;" type="submit">Найти</button>
                    <a class="btn" style="height:42px;display:inline-flex;align-items:center;" href="{$action}?tab=logs">Сбросить</a>
                </div>
            </form>
        HTML;
    }
}
