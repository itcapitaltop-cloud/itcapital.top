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
                    return $query
                        ->where('package_profits.created_at', '>=', now()->subMonth())
                        ->join('itc_packages as pkg', 'package_profits.package_uuid', '=', 'pkg.uuid')
                        ->join('transactions as tr', 'pkg.uuid', '=', 'tr.uuid')
                        ->join('users as u', 'tr.user_id', '=', 'u.id')
                        ->orderBy('u.username', $direction)
                        ->select('package_profits.*');
                }),

            Text::make('Пакет', 'package.uuid'),

            Number::make('Сумма пакета с реинвестами', 'package.total_amount')
                ->sortable(function ($query, string $column, string $direction) {
                    return $query
                        ->where('package_profits.created_at', '>=', now()->subMonth())
                        ->join('itc_packages as pkg2', 'package_profits.package_uuid', '=', 'pkg2.uuid')
                        ->join('transactions as tr2', 'pkg2.uuid', '=', 'tr2.uuid')
                        ->orderBy('tr2.amount', $direction)
                        ->select('package_profits.*');
                }),

            Number::make('Процент прибыли', 'package.month_profit_percent'),

            Number::make('Начислено', 'amount')
                ->sortable(),

            Text::make('Дата начисления', 'created_at'),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
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
     *
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
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
