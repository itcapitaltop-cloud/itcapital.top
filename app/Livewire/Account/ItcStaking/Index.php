<?php

declare(strict_types=1);

namespace App\Livewire\Account\ItcStaking;

use App\Actions\Staking\CreateStakingPackageAction;
use App\ActivityLog\ActivityManager;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Helpers\Notify;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\User\StakingStartBonusAccrualService;
use App\Settings\GeneralSetting;
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

        $package = CreateStakingPackageAction::make()
            ->run(auth()->id(), (float) $this->amount);

        $accrual = new StakingAccrualService()
            ->accrueTopUpStaking($package, (float) $this->amount, auth()->id());

        Notify::packageStakingBought(auth()->user(), $accrual->amount);

        $this->dispatch('bought');

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $this->amount);

        $this->js('window.location.reload()');
    }

    /**
     * @throws \Throwable
     */
    public function buyPackageMore(): void
    {
        $this->validate();

        $transaction = Transaction::query()
            ->where('user_id', auth()->id())
            ->whereHas('itcPackage', function ($query) {
                $query->where('type', PackageTypeEnum::STAKING);
            })
            ->with(['itcPackage'])
            ->first();

        new StakingAccrualService()->accrueTopUpStaking($transaction->itcPackage, (float) $this->amount, auth()->id());

        $transaction->increment('amount', $this->amount);

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $this->amount);

        $this->js('window.location.reload()');
    }

    protected function rules(): mixed
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
        return view('livewire.account.itc-staking.index', [
            'packages' => ItcPackage::query()->calculateStakingBalance(auth()->id())->get(),
            'lastMonthProfitability' => ItcPackage::query()->calculateStakingLastMonthProfitability(auth()->id())->first(),
            'totalProfitability' => ItcPackage::query()->calculateStakingTotalProfitability(auth()->id())->first(),
            'logs' => BusinessActivity::query()
                ->packagesStaking(auth()->id())
                ->latest()
                ->get()
                ->each(function (Activity $activity) {
                    $activity->text = new ActivityManager()->resolve($activity);

                    return $activity;
                }),
            'exchangeRateItc' => app(GeneralSetting::class)->exchange_rate_itc,
        ]);
    }
}
