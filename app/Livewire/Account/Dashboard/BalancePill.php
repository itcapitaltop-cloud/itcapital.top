<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Models\ItcPackage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BalancePill extends Component
{
    public string $class = '';

    public function mount(string $class = ''): void
    {
        $this->class = $class;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        // Only load data if user is authenticated
        if (! Auth::check()) {
            return view('livewire.account.dashboard.balance-pill', [
                'mainBalanceAmount' => 0,
                'partnerBalanceAmount' => 0,
                'balanceStaking' => 0,
            ]);
        }

        $transactionRepo = app(TransactionRepositoryContract::class);

        $balanceStaking = ItcPackage::query()
            ->calculateStakingBalance(auth()->id())
            ->get()
            ->sum(fn ($item) => $item->transaction_sum + $item->staking_accruals_sum);

        return view('livewire.account.dashboard.balance-pill', [
            'mainBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN),
            'partnerBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::PARTNER),
            'balanceStaking' => $balanceStaking,
        ]);
    }
}
