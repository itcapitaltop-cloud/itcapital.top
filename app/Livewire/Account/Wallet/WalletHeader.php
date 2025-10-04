<?php

namespace App\Livewire\Account\Wallet;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\BalanceTypeEnum;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WalletHeader extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        if (! Auth::check()) {
            return view('livewire.account.wallet.wallet-header', [
                'mainBalanceAmount' => 0,
            ]);
        }

        $transactionRepo = app(TransactionRepositoryContract::class);

        return view('livewire.account.wallet.wallet-header', [
            'mainBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN),
        ]);
    }
}
