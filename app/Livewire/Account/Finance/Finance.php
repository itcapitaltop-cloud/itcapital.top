<?php

namespace App\Livewire\Account\Finance;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\TransactionStatusEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Livewire\Forms\Account\Dashboard\CreateDepositForm;
use App\Livewire\Forms\Account\Dashboard\CreateWithdrawForm;
use App\Models\Deposit;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;
use Illuminate\Support\Str;

class Finance extends Component
{
    public CreateDepositForm $depositForm;
    public CreateWithdrawForm $withdrawForm;

    public function createDeposit()
    {
        $this->depositForm->store();

        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_finance_deposit_request_created')
        );

        $this->dispatch('deposit-created');
    }

    public function createWithdraw()
    {
        if (!Carbon::now()->isSunday()) {
            $this->dispatch(
                'new-system-notification',
                type: 'warning',
                message: __('livewire_finance_withdrawal_only_on_sunday')
            );
            return;
        }

        $this->withdrawForm->store(app(TransactionRepositoryContract::class));

        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_finance_withdrawal_request_created')
        );

        $this->withdrawForm->reset();

        $this->dispatch('withdraw-created');

    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof InvalidAmountException) {
            $this->dispatch('new-system-notification', type: 'error', message: $e->getMessage());
            $stopPropagation();
        }
    }

    public function mount()
    {
        // Finance или где создаётся форма
        $this->depositForm->depositAddress = config('wallet.deposit_address');
    }

    public function render()
    {
        $deposits = Deposit::query()
            ->join('transactions', 'deposits.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', Auth::id())
            ->select([
                'transactions.created_at',
                'transactions.amount',
                'transactions.accepted_at',
                'transactions.rejected_at',
                'deposits.payment_source_id',
                'deposits.currency',
                'deposits.transaction_hash',   // ← оставляем как есть
            ])
            ->with('paymentSource:id,source')   // crypto | fiat
            ->get()
            ->map(function (Deposit $d): array {

                $source = $d->paymentSource?->source;   // crypto | fiat

                if ($source === 'crypto') {
                    // пример: «USDT, tx 0xabc…789»
                    $tail = $d->currency->value
                        . ($d->transaction_hash
                            ? ', tx ' . Str::limit($d->transaction_hash, 10, '…')
                            : '');
                } else {
                    // пример: «Сбербанк»  (что пользователь ввёл в transaction_hash)
                    $tail = $d->transaction_hash ?: __('livewire_finance_bank_not_specified');
                }

                return [
                    'created_at' => $d->created_at,
                    'amount' => $d->amount,
                    'arrow' => 'down',
                    'type' => ($source === 'crypto'
                        ? __('livewire_finance_crypto_withdrawal_label', ['currency' => $tail])
                        : __('livewire_finance_fiat_withdrawal_label', ['bank' => $tail])),
                    'status' => TransactionStatusEnum::fromDates(
                        $d->accepted_at,
                        $d->rejected_at
                    )->getName(),
                ];
            })->toBase();

        $withdraws = Withdraw::query()
            ->join('transactions', 'withdraws.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', Auth::id())
            ->select([
                'transactions.created_at',
                'transactions.amount',
                'transactions.accepted_at',
                'transactions.rejected_at',
                'withdraws.payment_source_id',
                'withdraws.currency',
                'withdraws.wallet_address',
                'withdraws.uuid'
            ])
            ->with('paymentSource:id,source')        // crypto | fiat
            ->with('fiatDetail:uuid,bank_name')      // eager‑load, null для crypto
            ->get()
            ->map(function (Withdraw $w): array {
                $source = $w->paymentSource?->source;                 // crypto / fiat
                return [
                    'created_at' => $w->created_at,
                    'amount' => $w->amount,
                    'arrow' => 'up',
                    'type' => $source === 'crypto'
                        ? __('livewire_finance_crypto_withdrawal_label', ['currency' => $w->currency->value])
                        : __('livewire_finance_fiat_withdrawal_label', ['bank' => $w->fiatDetail?->bank_name]),
                    'status' => TransactionStatusEnum::fromDates(
                        $w->accepted_at, $w->rejected_at
                    )->getName(),
                ];
            })->toBase();

        /* общий журнал, сортируем по дате ↓ */
        $operations = $deposits
            ->merge($withdraws)
            ->sortByDesc('created_at')
            ->values();                              // сброс ключей

        return view('livewire.account.finance.finance', [
            'operations' => $operations,
        ]);
    }
}
