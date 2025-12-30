<?php

declare(strict_types=1);

namespace App\Livewire\Account\ItcStaking;

use App\ActivityLog\ActivityManager;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Helpers\Notify;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Tasks\Package\CreateItcStakingTask;
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
        $this->validate([
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
        ]);

        $package = new CreateItcStakingTask()->run($this->amount, auth()->user()->id);

        Notify::packageStakingBought(auth()->user(), $this->amount);

        $this->dispatch('bought');

        activity('packages')
            ->performedOn($package)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $this->amount,
                'package_uuid' => $package->uuid,
                'package_type' => PackageTypeEnum::STAKING,
            ])
            ->log('package_purchased');

        $this->js('window.location.reload()');
    }

    public function render(): Factory|View
    {
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();

        return view('livewire.account.itc-staking.index', [
            'packages' => ItcPackage::query()
                ->active(PackageTypeEnum::STAKING)
                ->userPackagesWithFinancials(auth()->user()->id)
                ->withSum(['profits as last_month_profit' => fn ($q) => $q->whereBetween('created_at', [$start, $end])], 'amount')
                ->get(),
            'logs' => BusinessActivity::query()
                ->packagesStaking(auth()->id())
                ->latest()
                ->get()
                ->each(function (Activity $activity) {
                      $activity->text =  new ActivityManager()->resolver($activity);

                      return $activity;
                }),
        ]);
    }
}
