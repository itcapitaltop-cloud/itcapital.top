<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Withdraw;

use App\Enums\Transactions\TransactionStatusEnum;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\MoonShine\Pages\User\UserDetailPage;
use App\MoonShine\Resources\UserResource;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Log;
use MoonShine\Exceptions\FieldException;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\TypeCasts\ModelCast;
use MoonShine\UI\ActionButtons\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Td;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;
use Throwable;

class WithdrawIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     *
     * @throws FieldException
     */
    public function fields(): array
    {
        return [
            Date::make('Дата заявки на вывод', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
            Text::make('Сумма', 'transaction', formatted: fn ($item) => round((float) $item->transaction->amount, 2))->showOnExport(),
            Text::make('Комиссия', 'commission', formatted: fn ($item) => round((float) $item->commission, 2))->showOnExport(),
            Text::make('К выводу', formatted: function (Withdraw $item) {
                // тут тоже можно логировать, если нужно
                return BigDecimal::of($item->transaction->amount)
                    ->minus($item->commission)
                    ->toScale(2, RoundingMode::HALF_UP)
                    ->stripTrailingZeros();
            })->showOnExport(),
            Url::make(
                'Пользователь',
                formatted: fn ($item) => to_page(
                    page: new UserDetailPage(),
                    resource: new UserResource(),
                    params: ['resourceItem' => $item->transaction?->user?->id]
                )
            )
                ->showOnExport(modifyRawValue: function ($href, Url $field) {
                    $user = $field->getData()->transaction?->user;

                    //                    Log::channel('source')->debug($user?->username ?? 'пользователь забанен');
                    return $user?->username ?? 'пользователь забанен';
                })
                ->title(
                    fn ($href, Url $field) => $field->getData()->transaction?->user?->username
                ),
            Text::make('Адрес/Карта/Счёт', 'payout_requisite')
                ->showOnExport(),
            Text::make('Крипто/Фиат', 'wallet_address', formatted: function (Withdraw $item) {
                $addr = $item->wallet_address;

                if (! is_string($addr) || $addr === '') {
                    return 'Фиат';
                }

                // Ethereum-адрес
                if (preg_match('/^0x[0-9A-Fa-f]{40}$/', $addr)) {
                    return 'Крипто (ETH)';
                }

                // Tron USDT (TRC-20)
                if (preg_match('/^T[a-zA-Z0-9]{33}$/', $addr)) {
                    return 'Крипто (USDT)';
                }

                // Bitcoin Legacy/P2PKH/P2SH или Bech32
                if (preg_match(
                    '/^(?:[13][a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[qpzry9x8gf2tvdw0s3jn54khce6mua7l]{39})$/i',
                    $addr
                )) {
                    return 'Крипто (BTC)';
                }

                return 'Фиат';
            })->showOnExport(),
            Td::make('Статус')
                ->fields(function (Td $field) {
                    $item = $field->getData();
                    $statusName = TransactionStatusEnum::fromDates(
                        $field->getData()?->transaction?->accepted_at,
                        $field->getData()?->transaction?->rejected_at
                    )->getName() ?? '';

                    if ($statusName === 'На модерации' && $item?->transaction) {
                        return [
                            ActionButton::make('')
                                ->method(
                                    'accept',
                                    params: fn () => ['resourceItem' => $item->uuid]
                                )
                                ->icon('heroicons.check')
                                ->success(),
                            ActionButton::make('')
                                ->method(
                                    'reject',
                                    params: fn () => ['resourceItem' => $item->uuid]
                                )
                                ->icon('heroicons.x-mark')
                                ->error(),
                        ];
                    }

                    return [
                        $statusName !== 'На модерации'
                            ? Text::make('Статус', formatted: fn () => $statusName
                                . (
                                    $statusName === TransactionStatusEnum::ACCEPTED->getName()
                                        ? ' (' . $item->transaction->accepted_at->format('d.m.Y H:i') . ')'
                                        : ''
                                )
                            )->showOnExport(modifyRawValue: fn ($text, Text $field) => TransactionStatusEnum::fromDates(
                                $field->getData()?->transaction?->accepted_at,
                                $field->getData()?->transaction?->rejected_at
                            )->getName()
                            )
                            : Text::make(''),
                        $statusName !== 'На модерации'
                            ? ActionButton::make('')
                                ->icon('pencil')
                                ->inModal(
                                    title: 'Редактировать заявку на вывод',
                                    content: function () use ($item) {
                                        /** @var Transaction $tx */
                                        $tx = $item->transaction;
                                        $currentStatus = TransactionStatusEnum::fromDates(
                                            $tx->accepted_at,
                                            $tx->rejected_at,
                                        );

                                        // Подготовка опций в зависимости от текущего статуса
                                        if ($currentStatus === TransactionStatusEnum::ACCEPTED) {
                                            $statusOptions = [
                                                TransactionStatusEnum::REJECTED->getName() => TransactionStatusEnum::REJECTED->getName(),
                                                TransactionStatusEnum::MODERATE->getName() => TransactionStatusEnum::MODERATE->getName(),
                                            ];
                                        } elseif ($currentStatus === TransactionStatusEnum::REJECTED) {
                                            $statusOptions = [
                                                TransactionStatusEnum::ACCEPTED->getName() => TransactionStatusEnum::ACCEPTED->getName(),
                                                TransactionStatusEnum::MODERATE->getName() => TransactionStatusEnum::MODERATE->getName(),
                                            ];
                                        }

                                        return FormBuilder::make()
                                            ->action(route('withdraw-update', ['uuid' => $item->uuid]))
                                            ->method('POST')
                                            ->fillCast(
                                                $tx,
                                                ModelCast::make(Transaction::class)
                                            )
                                            ->fields([
                                                Number::make('Сумма', 'amount'),
                                                Select::make('Статус заявки', 'status')
                                                    ->nullable()
                                                    ->options($statusOptions),
                                            ])
                                            ->async()
                                            ->submit('Сохранить');
                                    }
                                )
                            : Text::make(''),

                    ];
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
