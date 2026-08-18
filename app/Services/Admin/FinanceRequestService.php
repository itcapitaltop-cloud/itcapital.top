<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\Models\WithdrawFiatDetail;
use App\Services\ActivityLog\BusinessActivityLogger;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinanceRequestService
{
    public function __construct(private readonly BusinessActivityLogger $activityLogger) {}

    public function updateActiveAmount(string $uuid, string $amount, TrxTypeEnum $type): Transaction
    {
        return DB::transaction(function () use ($uuid, $amount, $type): Transaction {
            $transaction = Transaction::query()->lockForUpdate()->where('uuid', $uuid)->firstOrFail();

            if ($transaction->trx_type !== $type
                || $transaction->accepted_at !== null
                || $transaction->rejected_at !== null) {
                throw ValidationException::withMessages([
                    'amount' => 'Изменять сумму можно только у активной заявки.',
                ]);
            }

            $oldAmount = BigDecimal::of($transaction->amount);
            $newAmount = BigDecimal::of($amount)->toScale(2);
            $transaction->update(['amount' => $newAmount]);

            $this->logAmountUpdate($transaction, $type, $oldAmount, $newAmount);

            return $transaction->refresh();
        });
    }

    public function createWithdraw(
        User $user,
        string $amount,
        string $source,
        ?string $walletAddress = null,
        ?string $sbpPhone = null,
        ?string $bankName = null,
        ?string $recipientName = null,
    ): Withdraw {
        return DB::transaction(function () use (
            $user,
            $amount,
            $source,
            $walletAddress,
            $sbpPhone,
            $bankName,
            $recipientName,
        ): Withdraw {
            $paymentSourceId = PaymentSource::query()->where('source', $source)->value('id');

            if ($paymentSourceId === null) {
                throw ValidationException::withMessages([
                    'source' => 'Выбранный способ выплаты не настроен.',
                ]);
            }

            $transaction = Transaction::query()->create([
                'uuid' => 'WP-' . Str::random(10),
                'user_id' => $user->id,
                'amount' => BigDecimal::of($amount)->toScale(2),
                'balance_type' => BalanceTypeEnum::MAIN,
                'trx_type' => TrxTypeEnum::WITHDRAW,
            ]);

            $withdraw = Withdraw::query()->create([
                'uuid' => $transaction->uuid,
                'payment_source_id' => $paymentSourceId,
                'currency' => CurrencyEnum::fromNetwork(config('wallet.network')),
                'commission' => BigDecimal::of($amount)->multipliedBy('0.02')->plus('2')->toScale(2),
                'wallet_address' => $walletAddress,
            ]);

            if ($source === 'fiat') {
                WithdrawFiatDetail::query()->create([
                    'uuid' => $transaction->uuid,
                    'sbp_phone' => $sbpPhone,
                    'bank_name' => $bankName,
                    'recipient_name' => $recipientName,
                ]);
            }

            $this->activityLogger->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::WithdrawRequested,
                userId: $user->id,
                subject: $withdraw,
                feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => (string) $transaction->amount,
                    'currency' => $withdraw->currency->value,
                    'payment_source' => $source,
                    'wallet_address' => $walletAddress,
                    'bank_name' => $source === 'fiat' ? $bankName : null,
                ],
                causer: auth()->user(),
                logName: 'finance',
                context: 'admin',
            ));

            $this->activityLogger->writeDescription(
                description: LogActionTypeEnum::CREATE_WITHDRAW->value,
                userId: $user->id,
                subject: $transaction,
                feeds: [ActivityFeedTypeEnum::UserDetailAdmin, ActivityFeedTypeEnum::GlobalAdmin],
                properties: [
                    'old_values' => [],
                    'new_values' => ['amount' => (string) $transaction->amount],
                    'model_type' => Transaction::class,
                    'model_id' => $transaction->id,
                ],
                causer: auth()->user(),
                logName: 'admin',
                context: 'admin',
            );

            return $withdraw;
        });
    }

    private function logAmountUpdate(
        Transaction $transaction,
        TrxTypeEnum $type,
        BigDecimal $oldAmount,
        BigDecimal $newAmount,
    ): void {
        $difference = $newAmount->minus($oldAmount);

        $event = match (true) {
            $type === TrxTypeEnum::DEPOSIT => ActivityEventTypeEnum::DepositAmountChangedByAdmin,
            $difference->isPositive() => ActivityEventTypeEnum::WithdrawAmountIncreasedByAdmin,
            default => ActivityEventTypeEnum::WithdrawAmountDecreasedByAdmin,
        };

        $this->activityLogger->write(new WriteBusinessActivityData(
            type: $event,
            userId: $transaction->user_id,
            subject: $transaction,
            feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) ($type === TrxTypeEnum::DEPOSIT ? $newAmount : $difference->abs()),
                'old_amount' => (string) $oldAmount,
                'new_amount' => (string) $newAmount,
                'balance_difference' => $type === TrxTypeEnum::WITHDRAW
                    ? (string) $difference->negated()
                    : '0.00',
            ],
            causer: auth()->user(),
            logName: 'finance',
            context: 'admin',
        ));

        $this->activityLogger->writeDescription(
            description: ($type === TrxTypeEnum::DEPOSIT
                ? LogActionTypeEnum::UPDATE_DEPOSIT_AMOUNT
                : LogActionTypeEnum::UPDATE_WITHDRAW_AMOUNT)->value,
            userId: $transaction->user_id,
            subject: $transaction,
            feeds: [ActivityFeedTypeEnum::UserDetailAdmin, ActivityFeedTypeEnum::GlobalAdmin],
            properties: [
                'old_values' => ['amount' => (string) $oldAmount],
                'new_values' => ['amount' => (string) $newAmount],
                'difference' => (string) $difference,
                'model_type' => Transaction::class,
                'model_id' => $transaction->id,
            ],
            causer: auth()->user(),
            logName: 'admin',
            context: 'admin',
        );
    }
}
