<?php

namespace App\Livewire\Account\Wallet;

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use Livewire\Component;

class WithdrawsList extends Component
{
    public function render()
    {
        return view('livewire.account.wallet.withdraws-list', [
            'withdraws' => Transaction::query()
                            ->join('withdraws', 'transactions.uuid', '=', 'withdraws.uuid')
                            ->where('user_id', auth()->id())
                            ->where('trx_type', TrxTypeEnum::WITHDRAW)
                            ->select('transactions.uuid as uuid', 'transactions.created_at as created_at', 'accepted_at', 'rejected_at', 'user_id', 'commission', 'balance_type', 'amount')
                            ->paginate(10)
        ]);
    }
}
