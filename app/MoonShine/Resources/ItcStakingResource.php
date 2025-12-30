<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Itc\PackageTypeEnum;
use App\Helpers\Notify;
use App\Models\User;
use App\MoonShine\Pages\ItcStaking\ItcStakingDetailPage;
use App\MoonShine\Pages\ItcStaking\ItcStakingIndexPage;
use App\Tasks\Package\CreateItcStakingTask;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Number;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<\App\Models\User>
 */
class ItcStakingResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Cтейкинг';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            ItcStakingIndexPage::make($this->title()),
            ItcStakingDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    public function actions(): array
    {
        return [
            ActionButton::make('Доходность пакетов')->inModal(
                title: fn () => 'Настройка общей доходности для всех',
                content: function () {
                    return Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-staking/change/percentage')
                            ->fields([
                                Number::make('Сколько дивидентов начислить', 'percent')
                                    ->default(2)
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
        ];
    }

    public function trAttributes(): Closure
    {
        return function (User $item, int $row, ComponentAttributeBag $attr): ComponentAttributeBag {
            $url = to_page(
                page: new ItcStakingDetailPage(),
                resource: new ItcStakingResource(),
                params: [
                    'resourceItem' => $item->id,
                ],
            );

            $attr->setAttributes([
                'onclick' => "window.location='{$url}'",
                'style' => 'cursor: pointer;',
            ]);

            return $attr;
        };
    }

    /**
     * @return string[]
     */
    public function getActiveActions(): array
    {
        return ['view'];
    }

    /**
     * @return string[]
     */
    public function search(): array
    {
        return ['email', 'first_name', 'last_name', 'username', 'created_at'];
    }

    /**
     * @param \App\Models\User $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function createPackage(
        MoonShineRequest $request,
    ): MoonShineJsonResponse {

        $userId = (int) $request->input('user_id');
        $percent = (float) $request->input('percent');
        $amount = $request->input('amount');

        $package = new CreateItcStakingTask()
            ->setMothProfitPercent($percent)
            ->run($amount, $userId);

        activity('packages')
            ->performedOn($package)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $amount,
                'package_uuid' => $package->uuid,
                'package_type' => PackageTypeEnum::STAKING,
            ])
            ->log('admin_package_purchased');

        $url = to_page(
            page: new ItcStakingDetailPage(),
            resource: new self(),
            params: ['resourceItem' => $userId],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пакет создан')
            ->redirect($url);
    }
}
