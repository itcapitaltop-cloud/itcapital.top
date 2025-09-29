<?php

namespace App\Livewire\Account\Wallet;

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class DepositsList extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.account.wallet.deposits-list', [
            'deposits' => Transaction::query()->where('user_id', auth()->id())->where('trx_type', TrxTypeEnum::DEPOSIT)->paginate(10)
        ]);
    }
}
