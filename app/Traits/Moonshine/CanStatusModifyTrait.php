<?php

namespace App\Traits\Moonshine;

use App\Contracts\Logs\LogRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Helpers\Notify;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\Services\ActivityLog\BusinessActivityLogger;
use Illuminate\Support\Carbon;
use MoonShine\Enums\ToastType;
use MoonShine\Http\Responses\MoonShineJsonResponse;

trait CanStatusModifyTrait
{
    public function accept(): MoonShineJsonResponse
    {
        $transaction = Transaction::query()->firstWhere(['uuid' => $this->getItemID()]);

        $oldAccepted = $transaction->accepted_at;

        $transaction->accepted_at = Carbon::now();
        $transaction->rejected_at = null;
        $transaction->save();
        $u = User::where('id', $transaction->user_id)->first();

        if ($transaction->trx_type->value == 'deposit') {
            Notify::depositApproved($u, round((float) $transaction->amount));
            $this->logFinanceStatus($transaction, ActivityEventTypeEnum::DepositApproved);
        }

        if ($transaction->trx_type->value == 'withdraw') {
            Notify::withdrawApproved($u, round((float) $transaction->amount));
            $this->logFinanceStatus($transaction, ActivityEventTypeEnum::WithdrawApproved);
        }

        app(LogRepositoryContract::class)->updated(
            $transaction,
            'approve_transaction',
            ['accepted_at' => $oldAccepted?->toDateTimeString()],
            ['accepted_at' => $transaction->accepted_at->toDateTimeString()],
            $transaction->user->id
        );

        return MoonShineJsonResponse::make()
            ->toast('Отредактировано', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    public function reject(): MoonShineJsonResponse
    {
        $transaction = Transaction::query()->firstWhere(['uuid' => $this->getItemID()]);

        $oldRejected = $transaction->rejected_at;

        $transaction->accepted_at = null;
        $transaction->rejected_at = Carbon::now();
        $transaction->save();

        if ($transaction->trx_type->value === 'deposit') {
            $this->logFinanceStatus($transaction, ActivityEventTypeEnum::DepositRejected);
        }

        if ($transaction->trx_type->value === 'withdraw') {
            $this->logFinanceStatus($transaction, ActivityEventTypeEnum::WithdrawRejected);
        }

        app(LogRepositoryContract::class)->updated(
            $transaction,
            'reject_transaction',
            ['rejected_at' => $oldRejected?->toDateTimeString()],
            ['rejected_at' => $transaction->rejected_at->toDateTimeString()],
            $transaction->user->id
        );

        return MoonShineJsonResponse::make()
            ->toast('Отредактировано', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    public function toModerate(): MoonShineJsonResponse
    {
        $transaction = Transaction::query()->firstWhere(['uuid' => $this->getItemID()]);

        $oldAccepted = $transaction->accepted_at;
        $oldRejected = $transaction->rejected_at;

        $transaction->accepted_at = null;
        $transaction->rejected_at = null;
        $transaction->save();

        app(LogRepositoryContract::class)->updated(
            $transaction,
            'moderate_transaction',
            [
                'accepted_at' => $oldAccepted?->toDateTimeString(),
                'rejected_at' => $oldRejected?->toDateTimeString(),
            ],
            [
                'accepted_at' => null,
                'rejected_at' => null,
            ],
            $transaction->user->id
        );

        return MoonShineJsonResponse::make()
            ->toast('Отредактировано', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    protected function updateAmount(float $newAmount): Transaction
    {
        $transaction = Transaction::query()
            ->firstWhere('uuid', $this->getItemID());

        $oldAmount = $transaction->amount;
        $transaction->amount = $newAmount;
        $transaction->save();

        app(LogRepositoryContract::class)->updated(
            $transaction,
            'update_withdraw_amount',
            ['amount' => $oldAmount],
            ['amount' => $transaction->amount],
            $transaction->user->id
        );

        return $transaction;
    }

    private function logFinanceStatus(Transaction $transaction, ActivityEventTypeEnum $type): void
    {
        $deposit = Deposit::query()->where('uuid', $transaction->uuid)->first();
        $withdraw = Withdraw::query()->with('fiatDetail')->where('uuid', $transaction->uuid)->first();
        $subject = $deposit ?? $withdraw ?? $transaction;
        $currency = $deposit?->currency?->value ?? $withdraw?->currency?->value ?? 'ITC';
        $paymentSource = $deposit?->paymentSource?->source ?? $withdraw?->paymentSource?->source;
        $bankName = $withdraw?->fiatDetail?->bank_name
            ?? (($paymentSource === 'fiat') ? $deposit?->transaction_hash : null);

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: $type,
            userId: $transaction->user_id,
            subject: $subject,
            feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $transaction->amount,
                'currency' => $currency,
                'transaction_uuid' => $transaction->uuid,
                'payment_source' => $paymentSource,
                'bank_name' => $bankName,
            ],
            causer: auth()->user(),
            logName: 'finance',
            context: 'admin',
        ));
    }

    abstract public function getItemID(): int|null|string;
}
