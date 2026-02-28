<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\MoonShine\Pages\ItcStaking\ItcStakingDetailPage;
use App\MoonShine\Pages\ItcStaking\ItcStakingIndexPage;
use App\Settings\GeneralSetting;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Number;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<\App\Models\User>
 */
class ItcStakingResource extends ModelResource
{
    protected string $model = ItcPackage::class;

    protected string $title = 'Cтейкинг';

    protected array $with = ['transaction.user', 'stakingTransactionAccruals'];

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

    public function query(): Builder
    {
        return parent::query()
            ->where('type', PackageTypeEnum::STAKING)
            ->latest('created_at');
    }

    public function actions(): array
    {
        return [
            ActionButton::make('Изменить стартовую премию')->inModal(
                title: fn () => 'Изменение стартовой премии в процентах',
                content: function () {
                    return Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-staking/change/start-bonus-percentage')
                            ->fields([
                                Number::make('Укажите новый процент премии', 'percent')
                                    ->default(app(GeneralSetting::class)->start_bonus_staking_percent)
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
            ActionButton::make('Изменить регулярную премию')->inModal(
                title: fn () => 'Изменение регулярной премии в процентах',
                content: function () {
                    return Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-staking/change/regular-percentage')
                            ->fields([
                                Number::make('Укажите новый процент регулярной премии', 'percent')
                                    ->default(app(GeneralSetting::class)->regular_staking_percent)
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
        return function (ItcPackage $item, int $row, ComponentAttributeBag $attr): ComponentAttributeBag {
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
        return ['uuid'];
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
}
