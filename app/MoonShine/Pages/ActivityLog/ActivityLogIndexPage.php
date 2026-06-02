<?php
declare(strict_types=1);
namespace App\MoonShine\Pages\ActivityLog;

use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class ActivityLogIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Text::make('Ник', 'package.transaction.user.username')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('u.username', $direction);
                }),

            Text::make('Пакет', 'package.uuid')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('pkg.uuid', $direction);
                }),

            Number::make('Сумма пакета с реинвестами', 'package.total_amount')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('tr.amount', $direction);
                }),

            Number::make('Процент прибыли', 'package.month_profit_percent')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('pkg.month_profit_percent', $direction);
                }),

            Number::make('Начислено', formatted: fn ($item) => round((float) $item->amount, 2))
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('package_profits.amount', $direction);
                }),

            Text::make('Дата начисления', 'created_at')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('package_profits.created_at', $direction);
                }),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
