<?php

namespace App\Livewire\Account\Dashboard;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Services\Package\Staking\StakingPerformanceService;
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

        return view('livewire.account.dashboard.balance-pill', [
            'mainBalanceAmount' => $userSummary->investments_sum,
            'partnerBalanceAmount' => $userSummary->partner_balance,
            'balanceStaking' => $this->stakingBalance(),
        ]);
    }

    private function stakingBalance(): float
    {
        $packages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases', 'packageDefinition'])
            ->get();

        if ($packages->isEmpty()) {
            return 0.0;
        }

        return app(StakingPerformanceService::class)->forPackages($packages)['total_tokens'];
    }
}
