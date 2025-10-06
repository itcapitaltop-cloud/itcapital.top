<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Summary;

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\PartnerRankRequirement;
use App\Models\Transaction;
use App\Models\User;
use MoonShine\Contracts\MoonShineRenderable;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\Block;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Fields;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
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
        return [
            Heading::make('Пользователи')->h(5),

            Grid::make([
                ValueMetric::make('Всего')
                    ->value(User::count())
                    ->columnSpan(3),
                ValueMetric::make('Новые за неделю')
                    ->value(fn () => User::where('created_at', '>=', now()->startOfWeek())->count())
                    ->columnSpan(3),
                ValueMetric::make('Новые за сегодня')
                    ->value(fn () => User::whereDate('created_at', today())->count())
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
        return [
            Heading::make('Инвестиции')->h(5),

            Grid::make([
                // Всего депозитов
                ValueMetric::make(''
                )
                    // primary value (количество депозитов)
                    ->value(fn () => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->count()
                    )
                    // valueFormat — HTML с двумя числами в одну строку
                    ->valueFormat(fn (int $count): string =>
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
                                        (float) Transaction::query()
                                            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                            ->whereNotNull('accepted_at')
                                            ->sum('amount'),
                                        2
                                    )
                            . '</div>
                                <div class="text-label-report-card whitespace-normal mn-break-words">Сумма депозитов</div>
                            </div>
                        </div>'
                    )
                    ->columnSpan(3),

                // Новых за неделю
                ValueMetric::make('')
                    ->value(fn () => Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->where('accepted_at', '>=', now()->startOfWeek())
                        ->count()
                    )
                    ->valueFormat(fn (int $count): string => '<div class="mb-6 md:text-lg">Новые за неделю</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . $count . '</div>
                              <div class="text-label-report-card whitespace-normal mn-break-words">Количество депозитов</div>
                            </div>
                            <div class="block">
                            <div>
                        '
                        . round(
                            (float) Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                ->whereNotNull('accepted_at')
                                ->where('accepted_at', '>=', now()->startOfWeek())
                                ->sum('amount'),
                            2
                        ) . '</div>
                                <div class="text-label-report-card whitespace-normal mn-break-words">Сумма депозитов</div>
                            </div>
                        </div>'
                    )
                    ->columnSpan(3),

                // Новых за месяц
                ValueMetric::make('')
                    ->value(fn () => Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                        ->whereNotNull('accepted_at')
                        ->where('accepted_at', '>=', now()->startOfMonth())
                        ->count()
                    )
                    ->valueFormat(fn (int $count): string => '<div class="mb-6 md:text-lg">Новые за месяц</div>
                         <div class="flex justify-between text-lg">
                            <div class="block">
                              <div class="text-lg">' . $count . '</div>
                              <div class="text-label-report-card whitespace-normal mn-break-words">Количество депозитов</div>
                            </div>
                            <div class="block">
                            <div>
                        '
                        . round(
                            (float) Transaction::where('trx_type', TrxTypeEnum::DEPOSIT->value)
                                ->whereNotNull('accepted_at')
                                ->where('accepted_at', '>=', now()->startOfMonth())
                                ->sum('amount'),
                            2
                        ) . '</div>
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
                    ->value(fn () => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->count()
                    )
                    // две цифры в одной метрике: count и сумма
                    ->valueFormat(fn (int $count): string => '<div class="mb-6 md:text-lg">Всего</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                        (float) Transaction::query()
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
                    ->value(fn () => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->where('created_at', '>=', now()->startOfWeek())
                        ->count()
                    )
                    ->valueFormat(fn (int $count): string => '<div class="mb-6 md:text-lg">За неделю</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                        (float) Transaction::query()
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
                    ->value(fn () => Transaction::query()
                        ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count()
                    )
                    ->valueFormat(fn (int $count): string => '<div class="mb-6 md:text-lg">За месяц</div>
             <div class="flex justify-between text-lg">
               <div class="block">
                 <div class="text-lg">' . $count . '</div>
                 <div class="text-label-report-card whitespace-normal mn-break-words">Количество выводов</div>
               </div>
               <div class="block">
                 <div>' . round(
                        (float) Transaction::query()
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

        $walletModal = Modal::make(
            title: 'Смена адреса кошелька',
            content: fn () => null,
            asyncUrl: null,
            components: [$formWallet]
        )
            ->name('edit-wallet-modal');

        $percentModal = Modal::make(
            title: 'Настройка общих процентов премии',
            content: fn () => null,
            asyncUrl: route('modal.percents'),
            /* components: $componentsPercents */
        )
            ->name('edit-global-percents-modal')
            ->customAttributes(
                [
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
                    ->sticky()
                    ->name('requirements'),
            ])
            ->submit('Сохранить');

        $requirementsModal = Modal::make(
            title: 'Настройка требований к достижениям рангов',
            content: fn () => null,
            asyncUrl: null,
            components: [$formRequirements]
        )
            ->name('edit-rank-requirements-modal')
            ->customAttributes([
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
