<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Livewire\Forms\Account\Dashboard\CreateDepositForm;
use App\Livewire\Forms\Account\Dashboard\CreateWithdrawForm;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{

    public function render(): \Illuminate\Contracts\View\View
    {
        $transactionRepo = app(TransactionRepositoryContract::class);

        return view('livewire.account.dashboard.index', [

            'packagesCount'      => ItcPackage::query()
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('user_id', Auth::id())
                ->where('type', '!=', PackageTypeEnum::ARCHIVE)
                ->count(),

            'depositTotalAmount' => ItcPackage::query()
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('user_id', Auth::id())
                ->where('type', '!=', PackageTypeEnum::ARCHIVE)
                ->where(function ($q) {
                    $q->where('type', '!=', PackageTypeEnum::PRESENT)
                        ->orWhereDoesntHave('zeroing');
                })
                ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->get()
                ->sum(fn($p) => (float)$p->transaction->amount + (float)$p->reinvest_profits_sum_amount),

            'transactions'       => Transaction::query()
                ->where('user_id', Auth::id())
                ->whereNot('trx_type', TrxTypeEnum::HIDDEN_DEPOSIT)
                ->orderByDesc('created_at')
                ->get(),

            'partners'           => Partner::with(['user' => fn ($q) => $q->withoutGlobalScope('notBanned')])
                ->where('partner_id', Auth::id())
                ->whereHas('user', fn ($q) => $q->whereNull('banned_at'))
                ->limit(5)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Partner $p) => $p->user),

            // доход по пакетам: вся история и последняя неделя
            'yieldTotal' => PackageProfit::query()
                ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
                ->join('transactions',  'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', Auth::id())
                ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
                ->sum('package_profits.amount'),

            'yieldWeek'  => PackageProfit::query()
                ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
                ->join('transactions',  'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', Auth::id())
                ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
                ->where('package_profits.created_at', '>=', now()->subWeek())
                ->sum('package_profits.amount'),

            // распределение партнёров по линиям (до 5 уровней)
            'partnersInLines' => (function () {
                $lines  = [];
                $front  = [Auth::id()];

                for ($lvl = 1; $lvl <= 5; $lvl++) {
                    $ids = Partner::whereIn('partner_id', $front)
                        ->whereHas('user', fn ($q) => $q->whereNull('banned_at'))
                        ->pluck('user_id');

                    if ($ids->isEmpty()) break;

                    $lines[$lvl] = $ids->count();
                    $front       = $ids;           // переходим на следующий уровень
                }

                return $lines;
            })(),

            'partnersTotal' => (function () {
                $cnt = 0;
                $front = [Auth::id()];

                for ($lvl = 1; $lvl <= 5; $lvl++) {
                    $ids = Partner::whereIn('partner_id', $front)
                        ->whereHas('user', fn ($q) => $q->whereNull('banned_at'))
                        ->pluck('user_id');
                    if ($ids->isEmpty()) break;
                    $cnt  += $ids->count();
                    $front = $ids;
                }
                return $cnt;
            })(),

            // прирост первой линии
            'growthWeek'  => Partner::where('partner_id', Auth::id())
                ->whereBetween('created_at', [now()->subWeek(), now()])
                ->count(),

            'growthMonth' => Partner::where('partner_id', Auth::id())
                ->whereBetween('created_at', [now()->subMonth(), now()])
                ->count(),

            // Balance amounts
            'mainBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::MAIN),
            'partnerBalanceAmount' => $transactionRepo->getBalanceAmountByUserIdAndType(Auth::id(), BalanceTypeEnum::PARTNER),

            // Partner link
            'partnerLink' => url()->query('/', ['partner' => Auth::user()->username]),

            // Partner period stats
            'weekStats' => $transactionRepo->partnerPeriodStats(now()->subWeek()),
            'monthStats' => $transactionRepo->partnerPeriodStats(now()->subMonth()),
        ]);
    }
}
