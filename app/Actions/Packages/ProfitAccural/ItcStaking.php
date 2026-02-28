<?php

declare(strict_types=1);

namespace App\Actions\Packages\ProfitAccural;

use App\Contracts\ActionContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Helpers\Notify;
use App\Models\Transaction;
use App\Services\Package\Staking\StakingAccrualService;
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
        $accrual = new StakingAccrualService()
            ->accrueProfit($transaction->itcPackage, (float) $transaction->amount, $transaction->user->id);

        Notify::bonusStaking($transaction->user, $accrual->amount);
    }
}
