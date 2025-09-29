<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcPackage;

use App\Enums\Itc\PackageTypeEnum;
use App\MoonShine\Resources\TransactionResource;
use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use Throwable;

class ItcPackageIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Date::make('Дата покупки пакета', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
            Text::make('Пользователь', formatted: fn ($item) => $item->transaction?->user?->username)->showOnExport(
                modifyRawValue: fn ($value, $field) =>
                    $field->getData()->transaction?->user?->username ?? 'пользователь забанен'
            ),
            Text::make('Сумма', formatted: fn ($item) => round((float)$item->transaction?->amount, 2))->showOnExport(),
            Text::make('Сумма реинвеста', formatted: fn($item) => round((float)$item->reinvestProfits->sum('amount'), 2))->showOnExport(),
            Text::make('Дивидендов начислено всего', formatted: fn($item) => round((float)$item->profits->sum('amount'), 2))->showOnExport(),
            Text::make('Доходность пакета', formatted: fn ($item) => round((float)$item->month_profit_percent, 2))->showOnExport(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
