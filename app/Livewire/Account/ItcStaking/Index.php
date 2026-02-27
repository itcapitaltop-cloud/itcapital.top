<?php

declare(strict_types=1);

namespace App\Livewire\Account\ItcStaking;

use App\Actions\Staking\CalculateUserStakingSumAction;
use App\ActivityLog\ActivityManager;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Services\User\StakingStartBonusAccrualService;
use App\Settings\GeneralSetting;
use App\Tasks\Package\CreateItcStakingTask;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

        $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
        $token = $this->amount * $exchangeRateItc;
        $profit = $token - $this->amount;

        $package = new CreateItcStakingTask()->run($this->amount, auth()->user()->id);

        PackageProfit::query()
            ->create([
                'uuid' => 'SPP-' . Str::random(10),
                'package_uuid' => $package->uuid,
                'amount' => $profit,
            ]);

        Notify::packageStakingBought(auth()->user(), $token);

        $this->dispatch('bought');

        activity('packages')
            ->performedOn($package)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $token,
                'package_uuid' => $package->uuid,
                'package_type' => PackageTypeEnum::STAKING,
            ])
            ->log('package_purchased');

        new StakingStartBonusAccrualService()->accrue(auth()->id(), (float) $this->amount);

        $this->js('window.location.reload()');
    }

    /**
     * @throws \Throwable
     */
    public function buyPackageMore(): void
    {
        $this->validate();

        $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
        $token = $this->amount * $exchangeRateItc;
        $profit = $token - $this->amount;

        $package = Transaction::query()
            ->select(['id', 'uuid', 'amount', 'user_id'])
            ->where('user_id', auth()->user()->id)
            ->with([
                'itcPackage' => function ($query) {
                    $query->select(['id', 'uuid', 'month_profit_percent']);
                },
                'user' => function ($query) {
                    $query->select(['id']);
                },
            ])
            ->whereHas('itcPackage', function ($query) {
                $query->where('type', PackageTypeEnum::STAKING);
            })
            ->first();

        $package->increment('amount', $this->amount);

<<<<<<< Updated upstream
        PackageProfit::query()
=======
        StakingProfit::query()
>>>>>>> Stashed changes
            ->create([
                'uuid' => 'SPP-' . Str::random(10),
                'package_uuid' => $package->uuid,
                'amount' => $profit,
            ]);

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
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();
        dd(CalculateUserStakingSumAction::make()->run( auth()->id()));
        return view('livewire.account.itc-staking.index', [
            'packages' => ItcPackage::query()
                ->active(PackageTypeEnum::STAKING)
                ->userPackagesWithFinancials(auth()->user()->id)
                ->withSum(['profits as last_month_profit' => fn ($q) => $q->whereBetween('created_at', [$start, $end])], 'amount')
                ->get(),
            'regularPremium' => Transaction::where('user_id', Auth::id())
                ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
                ->whereIn('trx_type', [TrxTypeEnum::STAKING_START_BONUS_ACCRUAL, TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL])
                ->sum('amount'),
            'regularTotal' => Transaction::where('user_id', Auth::id())
                ->whereIn('trx_type', [TrxTypeEnum::STAKING_START_BONUS_ACCRUAL, TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL])
                ->sum('amount'),
            'regularWeek' => Transaction::where('user_id', Auth::id())
                ->whereIn('trx_type', [TrxTypeEnum::STAKING_START_BONUS_ACCRUAL, TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL])
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount'),
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
