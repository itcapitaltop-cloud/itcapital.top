<?php

namespace App\Composers;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\BalanceTypeEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthComposer
{
    public function __construct(
        protected TransactionRepositoryContract $transactionRepo
    ) {
    }

    public function compose(View $view): void
    {
//        if (!Auth::check()) return;
//
//        $mainBalanceAmount = $this->transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN);
//        $partnerBalanceAmount = $this->transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::PARTNER);
//        $weekStats = $this->transactionRepo->partnerPeriodStats(now()->subWeek());
//        $monthStats = $this->transactionRepo->partnerPeriodStats(now()->subMonth());
//        $partnerLink = url()->query('/', [
//            'partner' => Auth::user()->username
//        ]);
//
//        $view->with('mainBalanceAmount', $mainBalanceAmount);
//        $view->with('partnerBalanceAmount', $partnerBalanceAmount);
//        $view->with('partnerLink', $partnerLink);
//        $view->with('weekStats', $weekStats);
//        $view->with('monthStats', $monthStats);
    }
}
