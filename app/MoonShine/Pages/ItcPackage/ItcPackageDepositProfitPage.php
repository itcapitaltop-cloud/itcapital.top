<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcPackage;

use MoonShine\Components\Alert;
use MoonShine\Components\FormBuilder;
use MoonShine\Pages\Page;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Number;

class ItcPackageDepositProfitPage extends Page
{
    /**
     * @return array<string, string>
     */
    public function breadcrumbs(): array
    {
        return [
            '#' => $this->title()
        ];
    }

    public function title(): string
    {
        return $this->title ?: 'ItcPackageDepositProfitPage';
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
                                ])
                    ])
                    ->method('POST')
                    ->submit('Подтвердить')
            ])
        ];
    }
}
