<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcPackage;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MoonShine\Components\Alert;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\Modal;
use MoonShine\Components\Title;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\FormPage;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use MoonShine\Pages\Page;
use MoonShine\Pages\PageComponents;
use Throwable;

class ItcPackageFormPage extends Page
{
    public function title(): string
    {
        $uuid = request()->query('packageUuid');

        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->first();

//        Log::channel('source')->debug($package);

        return 'Редактирование акции '.$package->transaction->user->username;
    }

    public function components(): array
    {
        $uuid = request()->query('packageUuid');

        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->first();

        return [
            Modal::make(
                title: fn() => 'Редактирование акции '.$package->transaction->user->username,
                content: '',
                components: PageComponents::make([
                    Alert::make(type: 'info')->content('Изменение депозита приведет к изменению баланса (в том числе может стать отрицательным)'),
                    Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-packages/'.$uuid)
                            ->fields([
                                Date::make('Дата открытия', 'created_at')->fill($package->created_at),
                                Text::make('Процент прибыли', 'profit_percent')->fill($package->month_profit_percent),
                                Text::make('Депозит', 'amount')->fill($package->transaction->amount),
                                Enum::make('Тип пакета', 'type')->attach(PackageTypeEnum::class),
                            ])
                            ->method('POST')
                            ->submit('Подтвердить')
                    ]),
                    Block::make('Реинвест', [
                        Text::make('Реинвестировано раз')->fill(0)->disabled(),
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-packages/'.$uuid.'/reinvest')
                            ->method('POST')
                            ->submit('Реинвестировать')
                    ])
                ]))
                ->name('itc-package-modal')
                // вот этот вызов автоматически открывает модалку на загрузке страницы
                ->open(),
        ];
    }
}
