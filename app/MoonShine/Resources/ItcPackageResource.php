<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\User;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use App\MoonShine\Pages\User\UserDetailPage;
use Closure;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItcPackage;
use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageIndexPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageFormPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageDetailPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Resources\ModelResource;
use MoonShine\Pages\Page;

/**
 * @extends ModelResource<ItcPackage>
 */
class ItcPackageResource extends ModelResource
{
    protected string $model = ItcPackage::class;

    protected string $title = 'Пакеты';
    protected bool $editInModal   = true;

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            ItcPackageIndexPage::make($this->title()),
            ItcPackageFormPage::make('Редактирование пакета'),
            ItcPackageDetailPage::make(__('moonshine::ui.show')),
            ItcPackageDepositProfitPage::make('Начисление прибыли'),
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function actions(): array
    {
        return [
            ActionButton::make('Начислить прибыль', to_page(new ItcPackageDepositProfitPage))
        ];
    }

    /**
     * @param ItcPackage $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function trAttributes(): Closure
    {
        return function (ItcPackage $item, int $row, ComponentAttributeBag $attr): ComponentAttributeBag {
            $url = to_page(
                page:     new UserDetailPage,
                resource: new UserResource,
                params:   [
                    'resourceItem' => $item->transaction?->user?->id,
                    'tab'           => 'packages',
                    'openPackage'   => $item->uuid,
                ],
            );
            $attr->setAttributes([
                'onclick' => "window.location='{$url}'",
                'style'   => 'cursor: pointer;',
            ]);

            return $attr;
        };
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать',)
            ->spreadsheetId(env('ITC_PACKAGE_EXPORT_FILE_KEY'))
            ->disk('public')
            ->filename('itc-packages-'.now()->format('Ymd-His'))
            ->withConfirm();
    }
}
