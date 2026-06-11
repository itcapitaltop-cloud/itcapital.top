<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\PackageDefinition;

use App\Models\BusinessActivity;
use App\Models\Package\PackageDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\DetailPage;

class PackageDefinitionDetailPage extends DetailPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        $packageDefinition = $this->getResource()->getItem();

        if (! $packageDefinition instanceof PackageDefinition) {
            return [];
        }

        $eventSearch = trim((string) request('package_definition_activity_event', ''));

        // Keep the "Журнал" tab open after paginating or searching its table.
        $onJournal = request()->has('package_definition_activity_page') || $eventSearch !== '';
        $activeTab = $onJournal ? 'logs' : request('tab', 'data');

        return [
            Tabs::make([
                Tab::make('Данные', [
                    ID::make()->sortable(),
                    Text::make('Название', 'name'),
                    Text::make('Slug', 'slug'),
                    Text::make('Категория', 'type', formatted: fn (PackageDefinition $item): string => $item->type->getName()),
                    Number::make('Процент прибыли', 'default_profit_percent', formatted: fn (PackageDefinition $item): string => $item->default_profit_percent . '%'),
                    Number::make('Минимальная сумма', 'min_start_amount', formatted: fn (PackageDefinition $item): string => $item->min_start_amount),
                    Number::make('Срок, мес.', 'duration_months'),
                    Image::make('Изображение карточки', 'card_image_path')->disk('public')->dir('package-definitions'),
                    Text::make('Статус', formatted: fn (PackageDefinition $item): string => $item->trashed() ? 'Архив' : ($item->is_active ? 'Доступен в ЛК' : 'Только в админке')),
                    Number::make('Сортировка', 'sort_order'),
                ])->active(fn (): bool => $activeTab === 'data'),
                Tab::make('Журнал', $this->journalComponents($packageDefinition->id, $eventSearch))
                    ->active(fn (): bool => $activeTab === 'logs'),
            ]),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     */
    private function journalComponents(int $packageDefinitionId, string $eventSearch): array
    {
        $activities = BusinessActivity::query()
            ->where('subject_type', PackageDefinition::class)
            ->where('subject_id', $packageDefinitionId)
            ->with('causer')
            ->when($eventSearch !== '', function (Builder $query) use ($eventSearch): void {
                $query->whereRaw('lower(description) like ?', ['%' . mb_strtolower($eventSearch) . '%']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'package_definition_activity_page')
            ->appends(array_filter([
                'tab' => 'logs',
                'resourceItem' => request('resourceItem'),
                'package_definition_activity_event' => $eventSearch !== '' ? $eventSearch : null,
            ]));

        $rows = collect($activities->items())
            ->map(fn (BusinessActivity $activity): array => [
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
            FlexibleRender::make(fn (): string => $paginator->links('moonshine::ui.pagination')->toHtml()),
        ];
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
        $resourceItem = e((string) request('resourceItem', ''));

        return <<<HTML
            <form method="GET" action="{$action}" style="margin-bottom:16px;">
                <input type="hidden" name="resourceItem" value="{$resourceItem}">
                <input type="hidden" name="tab" value="logs">
                <span style="display:block;margin-bottom:4px;">Поиск по названию события</span>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input class="form-input" style="height:42px;min-width:280px;margin-bottom:0;" type="text" name="package_definition_activity_event" value="{$eventSearch}" placeholder="Например: Изменение тарифа">
                    <button class="btn btn-primary" style="height:42px;display:inline-flex;align-items:center;" type="submit">Найти</button>
                    <a class="btn" style="height:42px;display:inline-flex;align-items:center;" href="{$action}?resourceItem={$resourceItem}&tab=logs">Сбросить</a>
                </div>
            </form>
        HTML;
    }
}
