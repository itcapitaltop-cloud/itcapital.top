<?php

declare(strict_types=1);

namespace App\Livewire\Account\ItcStaking;

use App\ActivityLog\ActivityManager;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Helpers\Notify;
use App\Livewire\Concerns\WithInfiniteFeed;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Services\Package\Staking\StakingPerformanceService;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\User\SpendableBalanceResolver;
use App\Services\User\StakingStartBonusAccrualService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

final class Index extends Component
{
    use WithInfiniteFeed;

    #[Locked]
    public string $mainBalance;

    public string $amount;

    public function boot(TransactionRepositoryContract $transactionRepositoryContract): void
    {
        $this->mainBalance = $transactionRepositoryContract->getBalanceAmountByUserIdAndType(auth()->user()->id, BalanceTypeEnum::MAIN);
    }

    public function buyPackage(): void
    {
        $this->validate();

        $package = app(StakingPurchaseService::class)->createPackage(auth()->id(), (float) $this->spendableAmount());

        $purchase = $package->stakingPurchases()->latest('id')->firstOrFail();

        Notify::packageStakingBought(auth()->user(), $purchase->token_amount);

        $this->dispatch('bought');

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $purchase->token_amount);

        $this->js('window.location.reload()');
    }

    /**
     * @throws \Throwable
     */
    public function buyPackageMore(): void
    {
        $this->validate();

        $package = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->firstOrFail();

        $purchase = app(StakingPurchaseService::class)->addPurchase($package, (float) $this->spendableAmount(), auth()->id());

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $purchase->token_amount);

        $this->js('window.location.reload()');
    }

    /**
     * The amount that may actually be debited from the main balance.
     *
     * The balance is rendered at 2 decimals while it is stored at 8, so the figure
     * on screen can exceed the real balance by up to 0.005. Validation accepts the
     * displayed figure; this caps the debit at what the user truly has.
     */
    private function spendableAmount(): string
    {
        $resolver = app(SpendableBalanceResolver::class);
        $spendable = $resolver->clampToBalance($this->mainBalance, $this->amount);

        if ($resolver->wasClamped($this->mainBalance, $this->amount)) {
            Log::info('[FIX] staking purchase clamped to real balance', [
                'user_id' => auth()->id(),
                'requested_amount' => $this->amount,
                'displayed_balance' => $resolver->toDisplayScale($this->mainBalance),
                'real_balance' => $this->mainBalance,
                'debited_amount' => $spendable,
            ]);
        }

        return $spendable;
    }

    protected function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail): void {
                    if (! app(SpendableBalanceResolver::class)->coversRequestedAmount($this->mainBalance, (string) $value)) {
                        $fail('Недостаточно средств на балансе.');
                    }
                },
            ],
        ];
    }

    public function render(): Factory|View
    {
        $packages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases', 'packageDefinition'])
            ->get();

        $performanceService = app(StakingPerformanceService::class);
        $packagePerformances = $packages
            ->mapWithKeys(fn (ItcPackage $package): array => [$package->id => $performanceService->forPackage($package)]);
        $summaryPerformance = $performanceService->forPackages($packages);

        $stakingLogs = BusinessActivity::query()
            ->packagesStaking(auth()->id())
            ->latest()
            ->limit($this->feedFetchLimit())
            ->get()
            ->each(function (Activity $activity) {
                $activity->text = new ActivityManager()->resolve($activity);

                return $activity;
            });

        [$logs, $logsHasMore] = $this->paginateFeed($stakingLogs);

        return view('livewire.account.itc-staking.index', [
            'packages' => $packages,
            'packagePerformances' => $packagePerformances,
            'summaryPerformance' => $summaryPerformance,
            'lastMonthProfitability' => ItcPackage::query()->calculateStakingLastMonthProfitability(auth()->id())->first(),
            'logs' => $logs,
            'logsHasMore' => $logsHasMore,
            'exchangeRateItc' => $summaryPerformance['current_rate'],
        ]);
    }
}
