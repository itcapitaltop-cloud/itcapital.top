<?php

namespace App\Livewire\Account\Wallet;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Traits\Livewire\FormComponentTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateDeposit extends Component
{
    use FormComponentTrait;

    #[Validate(['required', 'numeric', 'min:0'])]
    public string $amount = '';
    #[Validate(['required', 'string', 'max:255'])]
    public string $transactionHash = '';

    public function onSubmit(): void
    {
        $uuid = 'DP-'.Str::random(10);

        DB::transaction(function () use ($uuid) {
            Transaction::query()->create([
                'uuid' => $uuid,
                'amount' => $this->amount,
                'trx_type' => TrxTypeEnum::DEPOSIT,
                'balance_type' => BalanceTypeEnum::MAIN,
                'user_id' => auth()->id(),
            ]);

            Deposit::query()->create([
                'transaction_hash' => $this->transactionHash,
                'uuid' => $uuid,
                'currency' => CurrencyEnum::USDT_TRC_20,
                'commission' => 0,
                'wallet_address' => 'test'
            ]);
        });

        $this->redirectRoute('wallet');
    }

    public function render()
    {
        return view('livewire.account.wallet.create-deposit');
    }
}
