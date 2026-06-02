<?php

namespace App\Livewire\Account\Dashboard;

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

        $userSummary = auth()->user()->summary;

        $balanceStaking = 100.0;

        return view('livewire.account.dashboard.balance-pill', [
            'mainBalanceAmount' => $userSummary->investments_sum,
            'partnerBalanceAmount' => $userSummary->partner_balance,
            'balanceStaking' => $balanceStaking,
        ]);
    }
}
