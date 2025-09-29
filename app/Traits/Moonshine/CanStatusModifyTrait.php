<?php

namespace App\Traits\Moonshine;

use App\Helpers\Notify;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use MoonShine\Enums\ToastType;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use App\Contracts\Logs\LogRepositoryContract;

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
            Notify::depositApproved($u, round((float)$transaction->amount));
        }

        if ($transaction->trx_type->value == 'withdraw') {
            Notify::withdrawApproved($u, round((float)$transaction->amount));
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

    abstract function getItemID(): int|null|string;
}
