<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcPackage;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Alert;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Block;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Number;

class ItcPackageDepositProfitPage extends Page
{
    protected string $title = 'Начислить прибыль';

    protected ?string $alias = 'itc-package-deposit-profit';

    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     */
    public function components(): array
    {
        return [
            Alert::make(type: 'info')->content('Прибыль начисляется за неделю')
                ->customAttributes([
                    // удаляем «резиновую» ширину, даём ширину по контенту
                    'style' => 'width: auto !important',   // `!` в Tailwind‑/daisyUI‑классах перекрывает `w-full`
                ]),
            Block::make([
                FormBuilder::make()
                    ->action('/itcapitalmoonshineadminpanel/itc-packages/profits/mass')
                    ->fields([
                        Number::make('Процент прибыли', 'profit_percent')
                            ->customAttributes(
                                [
                                    'step' => 'any',
                                ]),
                    ])
                    ->method('POST')
                    ->submit('Подтвердить'),
            ]),
        ];
    }
}
