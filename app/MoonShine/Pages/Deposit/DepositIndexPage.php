<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Deposit;

use App\Enums\Transactions\TransactionStatusEnum;
use App\Models\Deposit;
use App\Models\User;
use App\MoonShine\Pages\User\UserDetailPage;
use App\MoonShine\Resources\UserResource;
use Illuminate\Support\Facades\Log;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\ActionButtons\ActionButton;
use MoonShine\UI\Components\Dropdown;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Td;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;
use Throwable;

class DepositIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Date::make('Дата создания депозита', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
            Text::make(
                'Сумма транзакции',
                formatted: static fn ($item) => round((float) $item->transaction?->amount, 2)
            )
                ->showOnExport(),
            Url::make(
                'Пользователь',
                formatted: fn ($item) => to_page(
                    page: new UserDetailPage(),
                    resource: new UserResource(),
                    params: ['resourceItem' => $item->transaction?->user?->id]
                )
            )
                ->title(
                    function ($href, Url $field) {
                        return $field->getData()->transaction?->user?->username;
                    })
                ->showOnExport(modifyRawValue: function ($href, Url $field) {
                    $transaction = $field->getData()->transaction;

                    if (! $transaction) {
                        return '';
                    }

                    // вот здесь игнорируем глобальные скоупы на User
                    $user = User::withoutGlobalScope('notBanned')
                        ->find($transaction->user_id);

                    return $user
                        ? $user->username
                        : '';
                }),
            Text::make('Хеш транзакции', 'transaction_hash')->showOnExport(),
            Preview::make('', formatted: function (Deposit $item) {
                if (! preg_match('/^[A-Fa-f0-9]{64}$/', $item->transaction_hash ?? '')) {
                    return '';
                }

                return '<a href="https://tronscan.org/#/transaction/' . $item->transaction_hash . '" target="_blank" rel="noopener" title="Сканировать в Tronscan" style="display:inline-block;text-decoration:none;vertical-align:middle;"><svg xmlns="http://www.w3.org/2000/svg" style="width:1.5em;height:1.5em;vertical-align:middle;fill:currentColor;" viewBox="0 0 20 20"><path d="M10.186 2.003a8.013 8.013 0 1 0 7.812 6.288.75.75 0 0 0-1.469.292 6.51 6.51 0 1 1-1.396-2.446l-1.29.147a.75.75 0 0 0 .084 1.496l3.036-.346a.75.75 0 0 0 .662-.842l-.346-3.036a.75.75 0 1 0-1.496.084l.13 1.144A8.022 8.022 0 0 0 10.185 2ZM10 6.25a.75.75 0 0 1 .75.75v2.25h2.25a.75.75 0 0 1 0 1.5H10.75v2.25a.75.75 0 0 1-1.5 0V10.75H7a.75.75 0 0 1 0-1.5h2.25V7a.75.75 0 0 1 .75-.75Z"/></svg></a>';
            }),
            Text::make('Крипто/Фиат', 'transaction_hash', formatted: fn (Deposit $item) =>
                // если строка выглядит как 64-значный hex-хеш — считаем это криптовалютой
            preg_match('/^[A-Fa-f0-9]{64}$/', $item->transaction_hash)
                ? 'Крипто (USDT)'
                : "Фиат ({$item->transaction_hash})")->showOnExport(),
            Td::make('Статус')
                ->fields(function (Td $field) {
                    $item = $field->getData();
                    $statusName = TransactionStatusEnum::fromDates(
                        $item?->transaction?->accepted_at,
                        $item?->transaction?->rejected_at
                    )->getName();

                    $buttons = [];

                    //                    Log::channel('source')->debug($item);
                    // Если статус "На модерации" — показать две основные кнопки
                    if ($statusName === 'На модерации' && $item?->transaction) {
                        $buttons[] = ActionButton::make('')
                            ->method(
                                'accept',
                                params: fn () => ['resourceItem' => $item->uuid]
                            )
                            ->icon('heroicons.check')
                            ->success();

                        $buttons[] = ActionButton::make('')
                            ->method(
                                'reject',
                                params: fn () => ['resourceItem' => $item->uuid]
                            )
                            ->icon('heroicons.x-mark')
                            ->error();
                    }

                    // Если статус "Отклонено" — dropdown с "Исполнено" и "На модерации"
                    if ($statusName === 'Отклонено') {
                        // Сначала выводится статус
                        $buttons[] = Text::make('', formatted: fn () => $statusName)
                            ->showOnExport(
                                modifyRawValue: fn ($text, Text $field) => TransactionStatusEnum::fromDates(
                                    $field->getData()?->transaction?->accepted_at,
                                    $field->getData()?->transaction?->rejected_at
                                )->getName()
                            );
                        $buttons[] = Dropdown::make()
                            // собственный toggler вместо трёх точек :contentReference[oaicite:0]{index=0}
                            ->toggler(fn () => ActionButton::make('')
                                ->icon('pencil')
                            )
                            ->items([
                                ActionButton::make('На модерации')
                                    ->method(
                                        'toModerate',
                                        params: fn () => ['resourceItem' => $item->uuid]
                                    )
                                    ->icon('heroicons.clock'),

                                ActionButton::make('Исполнено')
                                    ->method(
                                        'accept',
                                        params: fn () => ['resourceItem' => $item->uuid]
                                    )
                                    ->icon('heroicons.check')
                                    ->success(),
                            ]);
                    }

                    // Если статус "Исполнено" — только статус
                    if ($statusName === 'Исполнено') {
                        $buttons[] = Text::make('', formatted: fn () => $statusName)
                            ->showOnExport(
                                modifyRawValue: fn ($text, Text $field) => TransactionStatusEnum::fromDates(
                                    $field->getData()?->transaction?->accepted_at,
                                    $field->getData()?->transaction?->rejected_at
                                )->getName()
                            );
                    }

                    return $buttons;
                }),
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
}
