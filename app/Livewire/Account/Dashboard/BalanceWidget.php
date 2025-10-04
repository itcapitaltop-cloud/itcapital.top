<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\BalanceTypeEnum;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BalanceWidget extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        if (! Auth::check()) {
            return view('livewire.account.dashboard.balance-widget', [
                'mainBalanceAmount' => 0,
                'partnerBalanceAmount' => 0,
            ]);
        }

        $transactionRepo = app(TransactionRepositoryContract::class);

        return view('livewire.account.dashboard.balance-widget', [
            'mainBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN),
            'partnerBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::PARTNER),
        ]);
    }
}
