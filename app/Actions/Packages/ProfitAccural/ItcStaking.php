<?php

declare(strict_types=1);

namespace App\Actions\Packages\ProfitAccural;

use App\Contracts\ActionContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Services\Package\Staking\StakingAccrualService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ItcStaking implements ActionContract
{
    /**
     * @return bool
     *
     * @throws \Throwable
     */
    public function execute(?CarbonInterface $asOf = null): bool
    {
        $asOf ??= now();

        DB::transaction(function () use ($asOf) {
            ItcPackage::query()
                ->select(['id', 'uuid', 'month_profit_percent'])
                ->with([
                    'transaction' => function ($query) {
                        $query->select(['id', 'uuid', 'amount', 'user_id']);
                    },
                    'transaction.user' => function ($query) {
                        $query->select(['id']);
                    },
                ])
                ->withSum('transaction as transaction_sum', 'amount')
                ->withSum(
                    ['stakingTransactionAccruals as staking_accruals_sum' => fn ($query) => $query
                        ->select(DB::raw('COALESCE(SUM(amount),0) / 100'))
                        ->where('created_at', '<=', $asOf)],
                    'amount'
                )
                ->where('type', PackageTypeEnum::STAKING)
                ->where('created_at', '<=', $asOf)
                ->chunkById(100, function (Collection $packages) {
                    $packages->each(function (ItcPackage $package) {
                        $this->profitAccrual($package);
                    });
                });
        });

        return true;
    }

    /**
     * @param \App\Models\ItcPackage $package
     * @return void
     */
    private function profitAccrual(ItcPackage $package): void
    {
        $packageAmount = round((float) ($package->transaction_sum ?? 0) + (float) ($package->staking_accruals_sum ?? 0), 2);

        $accrual = new StakingAccrualService()
            ->accrueProfit($package, $packageAmount, $package->transaction->user->id);

        Notify::bonusStaking($package->transaction->user, $accrual->amount);
    }
}
