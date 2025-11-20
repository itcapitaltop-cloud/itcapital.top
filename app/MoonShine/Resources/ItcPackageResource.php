<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\ItcPackage;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageDetailPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageFormPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageIndexPage;
use App\MoonShine\Pages\User\UserDetailPage;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<ItcPackage>
 */
class ItcPackageResource extends ModelResource
{
    protected string $model = ItcPackage::class;

    protected string $title = 'Пакеты';

    protected bool $editInModal = true;

    public function search(): array
    {
        return ['uuid'];
    }

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
            ActionButton::make('Начислить прибыль', to_page(new ItcPackageDepositProfitPage())),
            ActionButton::make('Начислить прибыль пакету')->inModal(
                title: fn () => 'Перечислите пакеты каким добавить прибыль',
                content: function () {
                    return Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-packages/profits/recalculate')
                            ->fields([
                                Select::make('Пакеты', 'uuid')
                                    ->options(
                                        ItcPackage::query()
                                            ->orderByDesc('created_at')
                                            ->pluck('uuid', 'uuid')
                                            ->toArray()
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->required(),
                                Number::make('Сколько дивидентов начислить', 'amount')
                                    ->customAttributes(
                                        [
                                            'step' => 'any',
                                        ])
                                    ->required(),
                            ])
                            ->method('POST')
                            ->submit('Подтвердить'),
                    ]);
                },
            )->primary(),
            ActionButton::make('Начислить регулярную премию')
                ->method('accrueRegularPremium')
                ->icon('heroicons.banknotes')
                ->success()
                ->showInDropdown(),
        ];
    }

    /**
     * @param ItcPackage $item
     * @return array<string, string[]|string>
     *
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
                page: new UserDetailPage(),
                resource: new UserResource(),
                params: [
                    'resourceItem' => $item->transaction?->user?->id,
                    'tab' => 'packages',
                    'openPackage' => $item->uuid,
                ],
            );
            $attr->setAttributes([
                'onclick' => "window.location='{$url}'",
                'style' => 'cursor: pointer;',
            ]);

            return $attr;
        };
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать')
            ->spreadsheetId(config('services.export_file.itc_package'))
            ->disk('public')
            ->filename('itc-packages-' . now()->format('Ymd-His'))
            ->withConfirm();
    }

    public function accrueRegularPremium(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            $userId = $request->input('user') ? (int) $request->input('user') : null;
            $reset = $request->input('reset', false);

            $command = 'regular-premium:accrual';
            $params = [];

            if ($userId) {
                $params['--user'] = $userId;
            }

            if ($reset) {
                $params['--reset'] = true;
            }

            Artisan::call($command, $params);

            return MoonShineJsonResponse::make()
                ->toast('Регулярная премия успешно начислена')
                ->redirect(request()->headers->get('referer'));
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }
}
