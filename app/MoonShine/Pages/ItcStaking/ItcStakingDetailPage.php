<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcStaking;

use App\ActivityLog\ActivityManager;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\MoonShine\Components\ItcPackages\Staking\ChangedRegularPercentComponent;
use App\MoonShine\Components\ItcPackages\Staking\ChangedStartBonusPercentComponent;
use App\MoonShine\Resources\ItcStakingResource;
use App\Services\Package\Staking\StakingPerformanceService;
use App\Settings\GeneralSetting;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Heading;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Field;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\DetailPage;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ItcStakingDetailPage extends DetailPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [];
    }

    public function components(): array
    {
        $package = $this->getResource()->getItem();

        $packages = ItcPackage::query()
            ->where('type', PackageTypeEnum::STAKING)
            ->whereHas('transaction', fn ($query) => $query->where('user_id', $package->transaction->user->id))
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
            ->get();

        $performanceService = app(StakingPerformanceService::class);
        $packageRows = $packages->map(function (ItcPackage $stakingPackage) use ($performanceService): array {
            return [
                ...$stakingPackage->toArray(),
                'performance' => $performanceService->forPackage($stakingPackage),
            ];
        });

        $adminLogs = BusinessActivity::query()
            ->packagesStakingWithAdmin($package->transaction->user->id)
            ->latest()
            ->get()
            ->each(function (Activity $activity) {
                $activity->text = new ActivityManager()->resolve($activity);

                return $activity;
            })
            ->toArray();

        $userLogs = BusinessActivity::query()
            ->packagesStaking($package->transaction->user->id)
            ->latest()
            ->get()
            ->each(function (Activity $activity) {
                $activity->text = new ActivityManager()->resolve($activity);

                return $activity;
            })
            ->toArray();

        $stakingChangedStartBonusPercent = new ChangedStartBonusPercentComponent()->handle($package);
        $stakingChangedSRegularPercent = new ChangedRegularPercentComponent()->handle($package);

        return [
            FlexibleRender::make(function () use ($stakingChangedStartBonusPercent, $stakingChangedSRegularPercent): string {
                return "<div class='flex flex-wrap gap-2 items-center'>
                    {$stakingChangedStartBonusPercent}
                    {$stakingChangedSRegularPercent}
                </div>";
            }),
            Tabs::make([
                Tab::make('Пакеты стейкинг', [
                    Heading::make("Пакеты {$package->transaction->user->username}")->h(2),
                    TableBuilder::make()
                        ->withNotFound()
                        ->items($packageRows->toArray())
                        ->fields([
                            Text::make('ID', 'uuid')->showOnExport(),
                            Date::make('Дата открытия', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
                            Text::make('Всего токенов', formatted: function (array $package): float {
                                return round((float) data_get($package, 'performance.total_tokens', 0), 2);
                            }),
                            Number::make('Вложено USD', formatted: function (array $package): float {
                                return round((float) data_get($package, 'performance.invested_usd', 0), 2);
                            }),
                            Number::make('Доходность в токенах', formatted: function (array $package): float {
                                return round((float) data_get($package, 'performance.yield_tokens', 0), 2);
                            }),
                            Number::make('Нереализованный P&L USD', formatted: function (array $package): float {
                                return round((float) data_get($package, 'performance.unrealized_pnl_usd', 0), 2);
                            }),
                            Number::make('Общая прибыль USD', formatted: function (array $package): float {
                                return round((float) data_get($package, 'performance.total_profit_usd', 0), 2);
                            }),
                            Number::make('Процент прибыли', 'month_profit_percent', formatted: function (array $package): string {
                                return $package['month_profit_percent'] . '%';
                            }),
                            Enum::make('Тип пакета', 'type')->attach(PackageTypeEnum::class),
                        ])
                        ->buttons([
                            ActionButton::make('')
                                ->inModal(
                                    title: static fn ($item) => 'Редактирование пакета',
                                    content: function (array $item): Block {
                                        $package = ItcPackage::whereUuid($item['uuid'])->firstOrFail();

                                        $components = [];

                                        if ($package->type !== PackageTypeEnum::ARCHIVE) {
                                            $components[] = $this->closePackageButton($item);
                                        } else {
                                            $components[] = Heading::make('В архиве')->h(5);
                                        }

                                        $components[] = $this->itcPackageEditForm($package, $item);

                                        return Block::make($components);
                                    },
                                    name: 'itc-package-modal'
                                )
                                ->icon('heroicons.pencil')
                                ->primary()
                                ->onClick(
                                    fn () => 'event.stopPropagation()',
                                    'stop'
                                ),
                            ActionButton::make('')
                                ->inModal(
                                    title: static fn ($item) => 'Ручной профит',
                                    content: function (array $item): Block {
                                        $package = ItcPackage::whereUuid($item['uuid'])->firstOrFail();

                                        return $this->itcPackageManualProfitForm($package);
                                    },
                                    name: 'itc-package-manual-profit-modal'
                                )
                                ->icon('heroicons.banknotes')
                                ->success()
                                ->onClick(
                                    fn () => 'event.stopPropagation()',
                                    'stop'
                                ),
                        ]),
                ]),

                Tab::make('Журнал', [
                    Tabs::make([
                        Tab::make('Администратор', [
                            TableBuilder::make()
                                ->withNotFound()
                                ->items($adminLogs)
                                ->fields([
                                    Date::make('Дата', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
                                    Text::make('Действие', 'text')->showOnExport(),
                                ]),
                        ]),
                        Tab::make('Пользователь', [
                            TableBuilder::make()
                                ->withNotFound()
                                ->items($userLogs)
                                ->fields([
                                    Date::make('Дата', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
                                    Text::make('Действие', 'text')->showOnExport(),
                                ]),
                        ]),
                    ]),
                ]),
            ]),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }

    public function breadcrumbs(): array
    {
        $package = $this->getResource()->getItem();

        return [
            to_page(
                page: new ItcStakingIndexPage(),
                resource: new ItcStakingResource()
            ) => __('Стейкинг'),

            '#' => $package->transaction?->user->id,
        ];
    }

    private function itcPackageEditForm(ItcPackage $package, array $item): Block
    {
        return Block::make([
            FormBuilder::make()
                ->action("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}")
                ->method('POST')
                ->async()
                ->fields([
                    Date::make('Дата открытия', 'created_at')
                        ->fill($package->created_at)
                        ->required(),

                    Number::make('Процент прибыли', 'profit_percent')
                        ->fill($package->month_profit_percent)
                        ->customAttributes(
                            [
                                'wire:model.defer' => 'percent',
                                'step' => 'any',
                            ])
                        ->required(),

                    Number::make('Процент стартовой премии', 'start_bonus_staking_percent')
                        ->fill($package->transaction->user->setting('start_bonus_staking_percent', app(GeneralSetting::class)->start_bonus_staking_percent))
                        ->customAttributes(
                            [
                                'wire:model.defer' => 'percent',
                                'step' => 'any',
                            ])
                        ->required(),

                    Number::make('Cумма будет добавлена в существующий пакет стейкинга', 'amount')
                        ->fill(0)
                        ->required(),

                    Hidden::make('manual_profit')
                        ->fill(0)
                        ->setValue(0),

                    Hidden::make('manual_accrual_type')
                        ->fill(StakingTransactionAccrualEnum::Profit->value)
                        ->setValue(StakingTransactionAccrualEnum::Profit->value),
                ])
                ->submit('Сохранить пакет'),
        ]);
    }

    private function itcPackageManualProfitForm(ItcPackage $package): Block
    {
        return Block::make([
            FormBuilder::make()
                ->action("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}")
                ->method('POST')
                ->async()
                ->fields([
                    Number::make('Сумма будет начислена сверху на пакет', 'manual_profit')
                        ->fill(0)
                        ->customAttributes([
                            'step' => 'any',
                        ])
                        ->required(),

                    Select::make('Тип начисления', 'manual_accrual_type')
                        ->options($this->manualAccrualTypeOptions())
                        ->default(StakingTransactionAccrualEnum::Profit->value)
                        ->required(),

                    Hidden::make('profit_percent')
                        ->fill($package->month_profit_percent)
                        ->setValue($package->month_profit_percent),

                    Hidden::make('amount')
                        ->fill(0)
                        ->setValue(0),
                ])
                ->submit('Начислить профит'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function manualAccrualTypeOptions(): array
    {
        return [
            StakingTransactionAccrualEnum::Profit->value => 'Начисление доходности',
            StakingTransactionAccrualEnum::TopUpBonus->value => 'Начисление токенов',
            StakingTransactionAccrualEnum::StartBonus->value => 'Стартовый бонус',
            StakingTransactionAccrualEnum::PartnerBonus->value => 'Партнерский бонус',
        ];
    }

    private function closePackageButton(array $item): ActionButton
    {
        return ActionButton::make('Закрыть пакет')
            ->icon('heroicons.archive-box')
            ->secondary()
            ->inModal(
                title: 'Подтверждение',
                content: fn () => Block::make([
                    Heading::make('Вы действительно хотите закрыть пакет?')->h(3),
                    Heading::make(
                        'Все средства будут выведены на основной баланс, а пакет станет архивным.'
                    )->h(6),
                    ActionButton::make(
                        'Подтвердить закрытие',
                        fn () => route('itc-package-close', ['uuid' => $item['uuid']])
                    )
                        ->icon('heroicons.archive-box')
                        ->async(method: 'POST')
                        ->secondary(),
                ])
            );
    }
}
