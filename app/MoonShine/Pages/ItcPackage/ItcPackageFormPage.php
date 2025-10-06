<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcPackage;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use Illuminate\Support\Facades\Log;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Alert;
use MoonShine\UI\Components\Block;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Text;

class ItcPackageFormPage extends Page
{
    public function getTitle(): string
    {
        $uuid = request()->query('packageUuid');

        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->first();

        //        Log::channel('source')->debug($package);

        return 'Редактирование акции ' . $package->transaction->user->username;
    }

    public function components(): array
    {
        $uuid = request()->query('packageUuid');

        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->first();

        return [
            Modal::make(
                title: fn () => 'Редактирование акции ' . $package->transaction->user->username,
                content: '',
                components: [
                    Alert::make(type: 'info')->content('Изменение депозита приведет к изменению баланса (в том числе может стать отрицательным)'),
                    Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-packages/' . $uuid)
                            ->fields([
                                Date::make('Дата открытия', 'created_at')->fill($package->created_at),
                                Text::make('Процент прибыли', 'profit_percent')->fill($package->month_profit_percent),
                                Text::make('Депозит', 'amount')->fill($package->transaction->amount),
                                Enum::make('Тип пакета', 'type')->attach(PackageTypeEnum::class),
                            ])
                            ->method('POST')
                            ->submit('Подтвердить'),
                    ]),
                    Block::make('Реинвест', [
                        Text::make('Реинвестировано раз')->fill(0)->disabled(),
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-packages/' . $uuid . '/reinvest')
                            ->method('POST')
                            ->submit('Реинвестировать'),
                    ]),
                ])
                ->name('itc-package-modal')
                // вот этот вызов автоматически открывает модалку на загрузке страницы
                ->open(),
        ];
    }
}
