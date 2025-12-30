<?php

declare(strict_types=1);

namespace App\Actions\Packages\ProfitAccural;

use App\Contracts\ActionContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Helpers\Notify;
use App\Models\Transaction;
use App\Tasks\Package\CreatePackageProfitTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ItcStaking implements ActionContract
{
    /**
     * @return bool
     *
     * @throws \Throwable
     */
    public function execute(): bool
    {
        DB::transaction(function () {
            Transaction::query()
                ->select(['id', 'uuid', 'amount', 'user_id'])
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
                ->chunkById(100, function (Collection $transactions) {
                    $transactions->each(function (Transaction $transaction) {
                        $this->profitAccrual($transaction);
                    });

                });
        });

        return true;
    }

    /**
     * @param \App\Models\Transaction $transaction
     * @return void
     */
    private function profitAccrual(Transaction $transaction): void
    {
        $package = $transaction->itcPackage;

        $amount = ($transaction->amount / 100) * $transaction->itcPackage->month_profit_percent;

        new CreatePackageProfitTask()->run($package->uuid, $amount);

        Notify::bonusStaking($transaction->user, $amount);

        activity('package')
            ->performedOn($package)
            ->causedBy($transaction->user)
            ->withProperties([
                'profit' => $amount,
                'percent' => $package->month_profit_percent,
                'transaction_uuid' => $transaction->uuid,
                'transaction_amount' => $transaction->amount,
                'package_uuid' => $package->uuid,
                'package_type' => PackageTypeEnum::STAKING,
            ])
            ->log('profit_accrued');
    }
}
