<?php

namespace App\Livewire\Account\Wallet;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\Withdraw;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateWithdraw extends Component
{
    #[Validate(['required', 'min:10', 'int'])]
    public string $amount = '';
    #[Validate(['required', 'string'])]
    public string $address = '';

    public function submit(TransactionRepositoryContract $transactionRepo)
    {
        $this->validate();

        $transactionRepo->checkBalanceAndStore(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::WITHDRAW,
            balanceType: BalanceTypeEnum::MAIN,
            amount: $this->amount,
            prefix: 'WW-'
        ), function (Transaction $transaction): Model {
            return Withdraw::query()->create([
                'uuid' => $transaction->uuid,
                'wallet_address' => $this->address,
                'commission' => BigDecimal::of($this->amount)->multipliedBy('0.02')->plus('2')->toScale(0, RoundingMode::CEILING),
                'currency' => CurrencyEnum::USDT_TRC_20
            ]);
        });
    }

    public function render()
    {
        return view('livewire.account.wallet.create-withdraw');
    }
}
