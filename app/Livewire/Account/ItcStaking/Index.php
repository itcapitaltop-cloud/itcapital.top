<?php

declare(strict_types=1);

namespace App\Livewire\Account\ItcStaking;

use App\ActivityLog\ActivityManager;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Helpers\Notify;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
<<<<<<< Updated upstream
use App\Services\Package\Staking\StakingPerformanceService;
use App\Services\Package\Staking\StakingPurchaseService;
=======
use App\Models\Transaction;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Services\Package\Staking\StakingAccrualService;
>>>>>>> Stashed changes
use App\Services\User\StakingStartBonusAccrualService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

final class Index extends Component
{
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

        $package = app(StakingPurchaseService::class)->createPackage(auth()->id(), (float) $this->amount);

<<<<<<< Updated upstream
        $purchase = $package->stakingPurchases()->latest('id')->firstOrFail();
=======
        $accrual = new StakingAccrualService()
            ->accrueTopUpStaking($package, (float) $this->amount, auth()->id(), false);

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::StakingPackagePurchased,
            userId: auth()->id(),
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Staking, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $this->amount,
                'package_uuid' => $package->uuid,
                'package_type' => PackageTypeEnum::STAKING->value,
            ],
            causer: auth()->user(),
            logName: 'packages',
            context: 'account',
        ));
>>>>>>> Stashed changes

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

        $purchase = app(StakingPurchaseService::class)->addPurchase($package, (float) $this->amount, auth()->id());

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $purchase->token_amount);

        $this->js('window.location.reload()');
    }

    protected function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($value > $this->mainBalance) {
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
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases'])
            ->get();

        $performanceService = app(StakingPerformanceService::class);
        $packagePerformances = $packages
            ->mapWithKeys(fn (ItcPackage $package): array => [$package->id => $performanceService->forPackage($package)]);
        $summaryPerformance = $performanceService->forPackages($packages);

        return view('livewire.account.itc-staking.index', [
            'packages' => $packages,
            'packagePerformances' => $packagePerformances,
            'summaryPerformance' => $summaryPerformance,
            'lastMonthProfitability' => ItcPackage::query()->calculateStakingLastMonthProfitability(auth()->id())->first(),
            'logs' => BusinessActivity::query()
                ->forUser(auth()->id())
                ->forFeed(ActivityFeedTypeEnum::Staking)
                ->latest()
                ->get()
                ->each(function (Activity $activity) {
                    $activity->text = new ActivityManager()->resolve($activity);

                    return $activity;
                }),
            'exchangeRateItc' => $summaryPerformance['current_rate'],
        ]);
    }
}
