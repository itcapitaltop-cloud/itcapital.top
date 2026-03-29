<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
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

        $transactionRepo = app(TransactionRepositoryContract::class);

        $packages = ItcPackage::query()
            ->whereHas('transaction', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
            ->get()
            ->filter(fn (ItcPackage $package) => $package->type === PackageTypeEnum::STAKING);

        $balanceStaking = app(StakingPerformanceService::class)->forPackages($packages)['total_tokens'];

        return view('livewire.account.dashboard.balance-pill', [
            'mainBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN),
            'partnerBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::PARTNER),
            'balanceStaking' => $balanceStaking,
        ]);
    }
}
