<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcStaking;

use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class ItcStakingIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Text::make('ID', 'uuid')->sortable(),
            Text::make('Дата покупки пакета', 'created_at')
                ->showOnExport()
                ->sortable(),
            Text::make('Пользователь', 'transaction.user.username'),
            Text::make('Сумма', formatted: fn ($item) => round((float) $item->transaction?->amount, 2))->showOnExport(),
            Text::make('Сумма реинвеста', formatted: fn ($item) => round((float) $item->reinvestProfits->sum('amount'), 2))->showOnExport(),
            Text::make('Дивидендов начислено всего', formatted: fn ($item) => round((float) $item->profits->sum('amount'), 2))->showOnExport(),
            Text::make('Доходность пакета', formatted: fn ($item) => round((float) $item->month_profit_percent, 2))->showOnExport(),
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
