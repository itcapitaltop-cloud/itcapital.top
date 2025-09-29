<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Summary;

use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\PartnerLevelPercent;
use App\Models\PartnerRankRequirement;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\Modal;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Grid;
use MoonShine\Decorations\Heading;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Number;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Select;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Metrics\ValueMetric;
use MoonShine\Pages\Crud\IndexPage;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Contracts\MoonShineRenderable;
use MoonShine\Fields\Field;
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
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            Heading::make('Пользователи')->h(5),

            Grid::make([
                ValueMetric::make('Всего')
                    ->value(User::count())
                    ->columnSpan(3),
                ValueMetric::make('Новые за неделю')
                    ->value(fn() => User::where('created_at', '>=', now()->startOfWeek())->count())
                    ->columnSpan(3),
                ValueMetric::make('Новые за сегодня')
                    ->value(fn() => User::whereDate('created_at', today())->count())
                    ->columnSpan(3),
            ]),
            Divider::make(),
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            Heading::make('Инвестиции')->h(5),

            Grid::make([
                // Всего депозитов
                ValueMetric::make(''
                )
                    // primary value (количество депозитов)
                    ->value(fn() => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->count()
                    )
                    // valueFormat — HTML с двумя числами в одну строку
                    ->valueFormat(fn(int $count): string =>
                        // пересчитываем сумму отдельно
                        '<div class="mb-6 md:text-lg">Всего</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . $count . '</div>
                              <div class="text-label-report-card whitespace-normal mn-break-words">Количество депозитов</div>
                            </div>
                            <div class="block">
                                <div>
                                '
                                    . round(
                                        (float)Transaction::query()
                                            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                            ->whereNotNull('accepted_at')
                                            ->sum('amount'),
                                        2
                                    )
                            .  '</div>
                                <div class="text-label-report-card whitespace-normal mn-break-words">Сумма депозитов</div>
                            </div>
                        </div>'
                    )
                    ->columnSpan(3),

                // Новых за неделю
                ValueMetric::make('')
                    ->value(fn() => Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->where('accepted_at', '>=', now()->startOfWeek())
                        ->count()
                    )
                    ->valueFormat(fn(int $count): string =>
                        '<div class="mb-6 md:text-lg">Новые за неделю</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . $count . '</div>
                              <div class="text-label-report-card whitespace-normal mn-break-words">Количество депозитов</div>
                            </div>
                            <div class="block">
                            <div>
                        '
                        . round(
                            (float)Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                ->whereNotNull('accepted_at')
                                ->where('accepted_at', '>=', now()->startOfWeek())
                                ->sum('amount'),
                            2
                        ) .  '</div>
                                <div class="text-label-report-card whitespace-normal mn-break-words">Сумма депозитов</div>
                            </div>
                        </div>'
                    )
                    ->columnSpan(3),

                // Новых за месяц
                ValueMetric::make('')
                    ->value(fn() => Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->where('accepted_at', '>=', now()->startOfMonth())
                        ->count()
                    )
                    ->valueFormat(fn(int $count): string =>
                        '<div class="mb-6 md:text-lg">Новые за месяц</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . $count . '</div>
                              <div class="text-label-report-card whitespace-normal mn-break-words">Количество депозитов</div>
                            </div>
                            <div class="block">
                            <div>
                        '
                        . round(
                            (float)Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                ->whereNotNull('accepted_at')
                                ->where('accepted_at', '>=', now()->startOfMonth())
                                ->sum('amount'),
                            2
                        ) .  '</div>
                                <div class="text-label-report-card whitespace-normal mn-break-words">Сумма депозитов</div>
                            </div>
                        </div>'
                    )
                    ->columnSpan(3),
            ]),

            Divider::make(),

            Heading::make('Выводы')->h(5),

            Grid::make([
                ValueMetric::make('')
                    // количество всех выводов
                    ->value(fn() => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->count()
                    )
                    // две цифры в одной метрике: count и сумма
                    ->valueFormat(fn(int $count): string =>
                        '<div class="mb-6 md:text-lg">Всего</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                            (float)Transaction::query()
                                ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                                ->sum('amount'),
                            2
                        ) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма выводов</div>
               </div>
             </div>'
                    )
                    ->columnSpan(3),

                ValueMetric::make('')
                    // count за неделю
                    ->value(fn() => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->where('created_at', '>=', now()->startOfWeek())
                        ->count()
                    )
                    ->valueFormat(fn(int $count): string =>
                        '<div class="mb-6 md:text-lg">За неделю</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                            (float)Transaction::query()
                                ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                                ->where('created_at', '>=', now()->startOfWeek())
                                ->sum('amount'),
                            2
                        ) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма выводов</div>
               </div>
             </div>'
                    )
                    ->columnSpan(3),

                ValueMetric::make('')
                    // count за месяц
                    ->value(fn() => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count()
                    )
                    ->valueFormat(fn(int $count): string =>
                        '<div class="mb-6 md:text-lg">За месяц</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                            (float)Transaction::query()
                                ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                                ->where('created_at', '>=', now()->startOfMonth())
                                ->sum('amount'),
                            2
                        ) . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Сумма выводов</div>
               </div>
             </div>'
                    )
                    ->columnSpan(3),
            ]),
        ];
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

    public function components(): array {
        $currentAddress = config('wallet.deposit_address');
        $currentNetwork = config('wallet.network');

        $formWallet = FormBuilder::make()
            ->asyncMethod('updateWallet')
            ->fields([
                Preview::make('Текущий адрес', formatted: fn () => $currentAddress),
                Preview::make('Текущая сеть',  formatted: fn () => $currentNetwork),

                Text::make('Новый адрес', 'address')
                    ->placeholder('Вставьте адрес кошелька')
                    ->required(),

                Select::make('Сеть', 'network')
                    ->options([
                        'ERC20'     => 'ERC20 (Ethereum)',
                        'BEP20'     => 'BEP20 (BNB Smart Chain)',
                        'POLYGON'   => 'Polygon (Matic)',
                        'ARBITRUM'  => 'Arbitrum One',
                        'OPTIMISM'  => 'Optimism',
                        'AVALANCHE' => 'Avalanche C-Chain',
                        'FANTOM'    => 'Fantom Opera',
                        'BASE'      => 'Base Mainnet',
                        'TRC20'     => 'TRC20 (Tron)',
                        'SOLANA'    => 'Solana (SPL)',
                    ])
                    ->required()
                    ->nullable(),
            ])
            ->submit('Сохранить');

        $componentsWallet = PageComponents::make([$formWallet]);

        $walletModal = Modal::make(
            title:      'Смена адреса кошелька',
            content:    fn () => null,
            asyncUrl:   null,
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
            title:      'Настройка общих процентов премии',
            content:    fn () => null,
            asyncUrl:   route('modal.percents'),
            /*components: $componentsPercents*/
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
            title:      'Настройка требований к достижениям рангов',
            content:    fn () => null,
            asyncUrl:   null,
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
