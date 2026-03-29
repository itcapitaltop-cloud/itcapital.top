<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\MoonShine\Pages\ItcStaking\ItcStakingDetailPage;
use App\MoonShine\Pages\ItcStaking\ItcStakingIndexPage;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Date;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
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
            ActionButton::make('Курс токена')->inModal(
                title: fn () => 'Изменение курса токена по дате',
                content: function () {
                    $tokenRateResolver = app(TokenRateResolver::class);
                    $tokenRatesCollection = \App\Models\TokenRate::query()
                        ->latest('effective_from')
                        ->get();
                    $activeEffectiveFrom = $tokenRatesCollection
                        ->filter(fn (\App\Models\TokenRate $tokenRate): bool => $tokenRate->effective_from !== null && ! $tokenRate->effective_from->isFuture())
                        ->max(fn (\App\Models\TokenRate $tokenRate): ?string => $tokenRate->effective_from?->toDateString());
                    $tokenRates = $tokenRatesCollection
                        ->map(fn (\App\Models\TokenRate $tokenRate): array => [
                            'id' => $tokenRate->id,
                            'effective_from' => $tokenRate->effective_from?->format('Y-m-d'),
                            'effective_from_human' => $tokenRate->effective_from?->format('d.m.Y'),
                            'rate' => (float) $tokenRate->rate,
                            'status' => $this->resolveTokenRateStatus($tokenRate, $activeEffectiveFrom),
                        ]);

                    return Block::make([
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/itc-staking/change/token-rate')
                            ->fields([
                                Hidden::make('token_rate_id')
                                    ->fill(''),
                                Date::make('Дата начала действия', 'effective_from')
                                    ->fill($tokenRateResolver->currentEffectiveFrom())
                                    ->required(),
                                Number::make('Курс токена в USD', 'rate')
                                    ->default($tokenRateResolver->currentRate())
                                    ->customAttributes([
                                        'step' => 'any',
                                        'min' => '0.000001',
                                    ])
                                    ->required(),
                            ])
                            ->async()
                            ->method('POST')
                            ->submit('Сохранить'),
                        TableBuilder::make()
                            ->withNotFound()
                            ->items($tokenRates->toArray())
                            ->fields([
                                Text::make('Дата', 'effective_from_human'),
                                Number::make('Курс USD', 'rate'),
                                Text::make('Статус', 'status'),
                            ])
                            ->buttons([
                                ActionButton::make('Редактировать', function ($item): string {
                                    if (! is_array($item)) {
                                        return '#';
                                    }

                                    $tokenRateId = (string) ($item['id'] ?? '');
                                    $effectiveFrom = (string) ($item['effective_from'] ?? '');
                                    $rate = (string) ($item['rate'] ?? '');

                                    return "javascript:(function(){document.querySelector('[name=\"token_rate_id\"]')&& (document.querySelector('[name=\"token_rate_id\"]').value='{$tokenRateId}');document.querySelector('[name=\"effective_from\"]')&& (document.querySelector('[name=\"effective_from\"]').value='{$effectiveFrom}');document.querySelector('[name=\"rate\"]')&& (document.querySelector('[name=\"rate\"]').value='{$rate}');})();";
                                })
                                    ->secondary()
                                    ->showInDropdown(),
                                ActionButton::make('Удалить', function ($item): string {
                                    if (! is_array($item) || ! isset($item['id'])) {
                                        return '#';
                                    }

                                    return route('admin.itc-staking.delete-token-rate', ['tokenRateId' => $item['id']]);
                                })
                                    ->async(method: 'POST')
                                    ->error()
                                    ->showInDropdown(),
                            ]),
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

    private function resolveTokenRateStatus(\App\Models\TokenRate $tokenRate, ?string $activeEffectiveFrom): string
    {
        if ($tokenRate->effective_from === null) {
            return 'Прошлый';
        }

        if ($tokenRate->effective_from->isFuture()) {
            return 'Не наступил';
        }

        if ($activeEffectiveFrom !== null && $tokenRate->effective_from->toDateString() === $activeEffectiveFrom) {
            return 'Активный';
        }

        return 'Прошлый';
    }
}
