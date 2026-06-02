<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Summary;

use App\Models\PartnerLevelPercent;
use App\Models\PartnerRankRequirement;
use App\Services\Admin\SummaryMetricsService;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\Modal;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Contracts\MoonShineRenderable;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Grid;
use MoonShine\Decorations\Heading;
use MoonShine\Fields\Field;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Number;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Metrics\DonutChartMetric;
use MoonShine\Metrics\ValueMetric;
use MoonShine\Pages\Crud\IndexPage;
use MoonShine\Pages\PageComponents;
use Throwable;

class SummaryIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
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
    protected function mainLayer(): array
    {
        $users = app(SummaryMetricsService::class)->snapshot()['users'];

        return [
            Heading::make('Пользователи')->h(5),

            Grid::make([
                ValueMetric::make('Всего')
                    ->value($users['total'])
                    ->columnSpan(3),
                ValueMetric::make('Новые за неделю')
                    ->value($users['week'])
                    ->columnSpan(3),
                ValueMetric::make('Новые за сегодня')
                    ->value($users['today'])
                    ->columnSpan(3),
            ]),
            Divider::make(),
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        $summary = app(SummaryMetricsService::class)->snapshot();
        $deposits = $summary['deposits'];
        $withdraws = $summary['withdraws'];
        $packages = $summary['packages'];
        $balances = $summary['balances'];
        $accruals = $summary['accruals'];

        return [
            Heading::make('Инвестиции')->h(5),

            Grid::make([
                // Всего депозитов
                ValueMetric::make('')
                    ->value($deposits['total_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'Всего',
                        $count,
                        'Количество депозитов',
                        $deposits['total_sum'],
                        'Сумма депозитов',
                    ))
                    ->columnSpan(3),

                // Новых за неделю
                ValueMetric::make('')
                    ->value($deposits['week_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'Новые за неделю',
                        $count,
                        'Количество депозитов',
                        $deposits['week_sum'],
                        'Сумма депозитов',
                    ))
                    ->columnSpan(3),

                // Новых за месяц
                ValueMetric::make('')
                    ->value($deposits['month_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'Новые за месяц',
                        $count,
                        'Количество депозитов',
                        $deposits['month_sum'],
                        'Сумма депозитов',
                    ))
                    ->columnSpan(3),
            ]),

            Divider::make(),

            Heading::make('Выводы')->h(5),

            Grid::make([
                ValueMetric::make('')
                    ->value($withdraws['total_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'Всего',
                        $count,
                        'Количество выводов',
                        $withdraws['total_sum'],
                        'Сумма выводов',
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($withdraws['week_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'За неделю',
                        $count,
                        'Количество выводов',
                        $withdraws['week_sum'],
                        'Сумма выводов',
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($withdraws['month_count'])
                    ->valueFormat(fn (int $count): string => $this->twoNumberCard(
                        'За месяц',
                        $count,
                        'Количество выводов',
                        $withdraws['month_sum'],
                        'Сумма выводов',
                    ))
                    ->columnSpan(3),
            ]),

            Divider::make(),

            Heading::make('Общая сумма в пакетах текущая')->h(5),

            DonutChartMetric::make('Пакеты')
                ->values($packages),

            Divider::make(),

            Grid::make([
                ValueMetric::make('')
                    ->value($packages['privilege'])
                    ->valueFormat(fn (float $count): string => '<div class="mb-6 md:text-lg">Пакеты privilege</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . round($count, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма</div>
               </div>
             </div>')
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($packages['standard'])
                    ->valueFormat(fn (float $count): string => '<div class="mb-6 md:text-lg">Пакеты standard</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . round($count, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма</div>
               </div>
             </div>')
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($packages['vip'])
                    ->valueFormat(fn (float $count): string => '<div class="mb-6 md:text-lg">Пакеты vip</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . round($count, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма</div>
               </div>
             </div>')
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($packages['present'])
                    ->valueFormat(fn (float $count): string => '<div class="mb-6 md:text-lg">Пакеты present</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . round($count, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма</div>
               </div>
             </div>')
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($packages['staking'] ?? 0)
                    ->valueFormat(fn (float $count): string => '<div class="mb-6 md:text-lg">Пакеты staking</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . round($count, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма</div>
               </div>
             </div>')
                    ->columnSpan(3),
            ]),

            Divider::make(),

            Heading::make('Общая сумма для всех аккаунтов')->h(5),

            Grid::make([
                ValueMetric::make('')
                    ->value($balances['main'])
                    ->valueFormat(fn (float $count): string => $this->singleNumberCard('Основной баланс', $count))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($balances['package_dividends'])
                    ->valueFormat(fn (float $count): string => $this->singleNumberCard('Дивиденды на пакетах', $count))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($balances['partner'])
                    ->valueFormat(fn (float $count): string => $this->singleNumberCard('Партнерский баланс', $count))
                    ->columnSpan(2),

                ValueMetric::make('')
                    ->value($balances['regular_premium'])
                    ->valueFormat(fn (float $count): string => $this->singleNumberCard('Регулярная премия', $count))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($balances['token'])
                    ->valueFormat(fn (float $count): string => $this->singleNumberCard('Баланс токенов', $count))
                    ->columnSpan(3),

            ]),

            Divider::make(),

            Heading::make('Начислено за месяц/неделю')->h(5),

            Grid::make([
                ValueMetric::make('')
                    ->value($accruals['dividends_month'])
                    ->valueFormat(fn (float $count): string => $this->monthWeekCard(
                        'Дивиденды',
                        $count,
                        $accruals['dividends_week'],
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($accruals['start_bonus_month'])
                    ->valueFormat(fn (float $count): string => $this->monthWeekCard(
                        'Стартовая премия',
                        $count,
                        $accruals['start_bonus_week'],
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($accruals['regular_premium_month'])
                    ->valueFormat(fn (float $count): string => $this->monthWeekCard(
                        'Регулярная премия',
                        $count,
                        $accruals['regular_premium_week'],
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($accruals['rank_bonus_month'])
                    ->valueFormat(fn (float $count): string => $this->monthWeekCard(
                        'Бонусы за достижения ранга',
                        $count,
                        $accruals['rank_bonus_week'],
                    ))
                    ->columnSpan(3),

                ValueMetric::make('')
                    ->value($accruals['staking_profits_month'])
                    ->valueFormat(fn (float $count): string => $this->monthWeekCard(
                        'Прибыли на пакетах токенов',
                        $count,
                        $accruals['staking_profits_week'],
                    ))
                    ->columnSpan(3),
            ]),

        ];
    }

    /**
     * Renders a metric card with two side-by-side numbers (count + sum).
     */
    private function twoNumberCard(
        string $title,
        int|float $primaryValue,
        string $primaryLabel,
        int|float $secondaryValue,
        string $secondaryLabel,
    ): string {
        return '<div class="mb-6 md:text-lg">' . $title . '</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $primaryValue . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">' . $primaryLabel . '</div>
               </div>
               <div class="block">
                 <div>' . round((float) $secondaryValue, 2) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">' . $secondaryLabel . '</div>
               </div>
             </div>';
    }

    /**
     * Renders a metric card with a single number.
     */
    private function singleNumberCard(string $title, int|float $value): string
    {
        return '<div class="mb-6 md:text-lg">' . $title . '</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . round((float) $value, 2) . '</div>
                            </div>
                        </div>';
    }

    /**
     * Renders a metric card comparing a monthly and a weekly figure.
     */
    private function monthWeekCard(string $title, int|float $monthValue, int|float $weekValue): string
    {
        return $this->twoNumberCard(
            $title,
            round((float) $monthValue, 2),
            'Месяц',
            $weekValue,
            'Неделя',
        );
    }

    protected function itemsComponent(iterable $items, Fields $fields): MoonShineRenderable
    {
        return Block::make('')->customAttributes([
            'style' => '
                background: none !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            ',
        ]);
    }

    public function components(): array
    {
        $currentAddress = config('wallet.deposit_address');
        $currentNetwork = config('wallet.network');

        $formWallet = FormBuilder::make()
            ->asyncMethod('updateWallet')
            ->fields([
                Preview::make('Текущий адрес', formatted: fn () => $currentAddress),
                Preview::make('Текущая сеть', formatted: fn () => $currentNetwork),

                Text::make('Новый адрес', 'address')
                    ->placeholder('Вставьте адрес кошелька')
                    ->required(),

                Select::make('Сеть', 'network')
                    ->options([
                        'ERC20' => 'ERC20 (Ethereum)',
                        'BEP20' => 'BEP20 (BNB Smart Chain)',
                        'POLYGON' => 'Polygon (Matic)',
                        'ARBITRUM' => 'Arbitrum One',
                        'OPTIMISM' => 'Optimism',
                        'AVALANCHE' => 'Avalanche C-Chain',
                        'FANTOM' => 'Fantom Opera',
                        'BASE' => 'Base Mainnet',
                        'TRC20' => 'TRC20 (Tron)',
                        'SOLANA' => 'Solana (SPL)',
                    ])
                    ->required()
                    ->nullable(),
            ])
            ->submit('Сохранить');

        $componentsWallet = PageComponents::make([$formWallet]);

        $walletModal = Modal::make(
            title: 'Смена адреса кошелька',
            content: fn () => null,
            asyncUrl: null,
            components: $componentsWallet
        )
            ->name('edit-wallet-modal');

        //        $formPercents = FormBuilder::make()
        //            ->name('global-percent-form')
        //            ->asyncMethod('saveGlobalPercents')
        //            ->fields([
        //                TableBuilder::make()
        //                    ->editable()
        //                    ->fields([
        //                        Number::make('Ранг', 'partner_level_id')
        //                            ->customAttributes(['class' => 'input-invisible rank-width'])->readonly(),
        //                        Text::make('Тип премии', 'bonus_type')
        //                            ->customAttributes(['class' => 'input-invisible'])
        //                            ->readonly(),
        //                        Number::make('Линия 1', 'line_1')
        //                            ->step(0.01),
        //                        Number::make('Линия 2', 'line_2')
        //                            ->step(0.01),
        //                        Number::make('Линия 3', 'line_3')
        //                            ->step(0.01),
        //                        Number::make('Линия 4', 'line_4')
        //                            ->step(0.01),
        //                        Number::make('Линия 5', 'line_5')
        //                            ->step(0.01),
        //                        Number::make('Линия 6', 'line_6')
        //                            ->step(0.01),
        //                        Number::make('Линия 7', 'line_7')
        //                            ->step(0.01),
        //                        Number::make('Линия 8', 'line_8')
        //                            ->step(0.01),
        //                        Number::make('Линия 9', 'line_9')
        //                            ->step(0.01),
        //                        Number::make('Линия 10', 'line_10')
        //                            ->step(0.01),
        //                        Number::make('Линия 11', 'line_11')
        //                            ->step(0.01),
        //                        Number::make('Линия 12', 'line_12')
        //                            ->step(0.01),
        //                        Number::make('Линия 13', 'line_13')
        //                            ->step(0.01),
        //                        Number::make('Линия 14', 'line_14')
        //                            ->step(0.01),
        //                        Number::make('Линия 15', 'line_15')
        //                            ->step(0.01),
        //                        Number::make('Линия 16', 'line_16')
        //                            ->step(0.01),
        //                        Number::make('Линия 17', 'line_17')
        //                            ->step(0.01),
        //                        Number::make('Линия 18', 'line_18')
        //                            ->step(0.01),
        //                        Number::make('Линия 19', 'line_19')
        //                            ->step(0.01),
        //                        Number::make('Линия 20', 'line_20')
        //                            ->step(0.01),
        //                    ])
        //                    ->items(
        //                        PartnerLevelPercent::asGridRows(common: true)
        //                    )
        //                    ->customAttributes(
        //                        [
        //                            'style' => 'width:1200px;',
        //                            'class' => 'table-partners-percents',
        //                        ])
        //                    ->tdAttributes(
        //                        function (mixed $data, int $row, int $cell, ComponentAttributeBag $attr) {
        //                            if ($cell === 0) {
        //                                $existing = $attr->get('class', '');
        //                                $attr->setAttributes([
        //                                    'class' => trim($existing),
        //                                    'style' => 'position:sticky;left:0;background:#fff;',
        //                                ]);
        //                            }
        //                            if ($cell === 1) {
        //                                $existing = $attr->get('class', '');
        //                                $attr->setAttributes([
        //                                    'class' => trim($existing),
        //                                    'style' => 'position:sticky;left:80px;min-width:100px;max-width:100px;width:100px;',
        //                                ]);
        //                            }
        //                            if ($cell >= 2) {
        //                                $existing = $attr->get('class', '');
        //                                $attr->setAttributes([
        //                                    'class' => trim($existing),
        //                                    'style' => 'min-width:140px;max-width:140px;width:140px;',
        //                                ]);
        //                            }
        //                            return $attr;
        //                        }
        //                    )
        //                    ->sticky()
        //                    ->name('percentsCommon'),
        //            ])
        //            ->submit('Сохранить');
        //
        //        $componentsPercents = PageComponents::make([$formPercents]);

        $percentModal = Modal::make(
            title: 'Настройка общих процентов премии',
            content: fn () => null,
            asyncUrl: route('modal.percents'),
            /* components: $componentsPercents */
        )
            ->name('edit-global-percents-modal')
            ->customAttributes(
                [
                    'style' => 'overflow-x:scroll;',
                    'class' => 'modal-change-percents',
                ]);

        $formRequirements = FormBuilder::make()
            ->name('rank-requirements-form')
            ->asyncMethod('saveRankRequirements')
            ->fields([
                TableBuilder::make()
                    ->editable()
                    ->fields([
                        Number::make('Ранг', 'partner_rank_id')
                            ->customAttributes(['class' => 'input-invisible'])->readonly(),
                        Number::make('Личный депозит', 'personal_deposit')
                            ->step(0.01),
                        Number::make('Линия 1', 'line_1')
                            ->step(0.01),
                        Number::make('Линия 2', 'line_2')
                            ->step(0.01),
                        Number::make('Линия 3', 'line_3')
                            ->step(0.01),
                        Number::make('Линия 4', 'line_4')
                            ->step(0.01),
                        Number::make('Линия 5', 'line_5')
                            ->step(0.01),
                        Number::make('Бонус, $', 'bonus_usd')
                            ->step(0.01),
                    ])
                    ->items(
                        PartnerRankRequirement::asGridRows()
                    )
                    ->customAttributes(['style' => 'width:1200px;'])
                    ->tdAttributes(
                        function (mixed $data, int $row, int $cell, ComponentAttributeBag $attr) {
                            if ($cell === 0) {
                                $existing = $attr->get('class', '');
                                $attr->setAttributes([
                                    'class' => trim($existing),
                                    'style' => 'position:sticky;left:0;background:#fff;',
                                ]);
                            }

                            return $attr;
                        }
                    )
                    ->sticky()
                    ->name('requirements'),
            ])
            ->submit('Сохранить');

        $componentsRequirements = PageComponents::make([$formRequirements]);

        $requirementsModal = Modal::make(
            title: 'Настройка требований к достижениям рангов',
            content: fn () => null,
            asyncUrl: null,
            components: $componentsRequirements
        )
            ->name('edit-rank-requirements-modal')
            ->customAttributes([
                'style' => 'overflow-x:scroll;',
                'class' => 'modal-change-requirements',
            ]);

        return [
            ...parent::components(),
            $walletModal,
            $percentModal,
            $requirementsModal,
        ];
    }
}
