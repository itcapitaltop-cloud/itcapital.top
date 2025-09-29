<?php

namespace App\Livewire\Forms\Account\Dashboard;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Deposit;
use App\Models\PaymentSource;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Illuminate\Support\Str;

class CreateDepositForm extends Form
{
    #[Validate(['required', 'numeric', 'min:0'])]
    public string $depositAmount = '';
    #[Validate(['required', 'string', 'max:255'])]
    public string $transactionHash = '';
    #[Validate(['required', 'in:crypto,fiat'])]
    public string $depositSource = 'crypto';

    #[Validate(['string', 'max:255'])]
    public string $depositAddress = '';

    public function store()
    {

        $this->validate();

        $sourceId = PaymentSource::where('source', $this->depositSource)->value('id');

        $network = config('wallet.network');

        $uuid = 'DP-' . Str::random(10);

        DB::transaction(function () use ($network, $uuid, $sourceId) {
            Transaction::query()->create([
                'uuid' => $uuid,
                'amount' => $this->depositAmount,
                'trx_type' => TrxTypeEnum::DEPOSIT,
                'balance_type' => BalanceTypeEnum::MAIN,
                'user_id' => auth()->id(),
            ]);

            Deposit::create([
                'uuid'              => $uuid,
                'payment_source_id' => $sourceId,
                'transaction_hash'  => $this->transactionHash,
                'currency'          => CurrencyEnum::fromNetwork($network),
                'commission'        => 0,
                'wallet_address'    => config('wallet.deposit_address'),
            ]);
        });

        $this->resetExcept('depositAddress');
    }
}
