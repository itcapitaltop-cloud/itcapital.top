<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Partner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        $transactionRepo = app(TransactionRepositoryContract::class);
        $userId = Auth::id();

        // Обход партнёрского дерева (до 5 уровней) считаем один раз:
        // отсюда получаем и распределение по линиям, и общее число партнёров.
        $partnersInLines = $this->partnersByLine();

        // Reconciliation reference: the ITC Packages tab (/account/itc), which uses
        // ItcPackage::notActive() — excludes only ARCHIVE/STAKING, keeps PRESENT.
        $packagesCount = ItcPackage::query()
            ->whereHas('transaction.user', fn ($query) => $query->whereId($userId))
            ->notActive()
            ->count();

        // "Сумма пакетов" must mirror each ITC card: package body
        // (transaction.amount + partnerTransfers + reinvestToBody − balanceWithdraws,
        // forced to 0 for zeroed PRESENT) plus the active "+Реинвестировано" amount.
        $depositTotalAmount = ItcPackage::query()
            ->select('itc_packages.*')
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('user_id', $userId)
            ->notActive()
            ->with(['transaction', 'zeroing'])
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->get()
            ->sum(function (ItcPackage $package): float {
                $body = $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                    ? 0.0
                    : (float) $package->transaction->amount
                        + (float) $package->partner_transfers_sum_amount
                        + (float) $package->reinvest_to_body_sum_amount
                        - (float) $package->balance_withdraws_sum_amount;

                return $body + (float) $package->reinvest_profits_sum_amount;
            });

        $yieldTotal = PackageProfit::query()
            ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $userId)
            ->whereNotIn('itc_packages.type', ItcPackage::NOT_ACTIVE_EXCLUDED_TYPES)
            ->sum('package_profits.amount');

        $yieldWeek = PackageProfit::query()
            ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $userId)
            ->whereNotIn('itc_packages.type', ItcPackage::NOT_ACTIVE_EXCLUDED_TYPES)
            ->where('package_profits.created_at', '>=', now()->subWeek())
            ->sum('package_profits.amount');

        $partnerWeekAccrual = $transactionRepo->partnerGrossAccrualSince(now()->subWeek());
        $partnerMonthAccrual = $transactionRepo->partnerGrossAccrualSince(now()->subMonth());

        return view('livewire.account.dashboard.index', [

            'packagesCount' => $packagesCount,

            'depositTotalAmount' => $depositTotalAmount,

            // доход по пакетам: вся история и последняя неделя
            'yieldTotal' => $yieldTotal,

            'yieldWeek' => $yieldWeek,

            // распределение партнёров по линиям (до 5 уровней) и их общее число —
            // считаются из одного обхода дерева (см. $partnersInLines выше)
            'partnersInLines' => $partnersInLines,

            'partnersTotal' => array_sum($partnersInLines),

            // прирост первой линии
            'growthWeek' => Partner::where('partner_id', Auth::id())
                ->whereBetween('created_at', [now()->subWeek(), now()])
                ->count(),

            'growthMonth' => Partner::where('partner_id', Auth::id())
                ->whereBetween('created_at', [now()->subMonth(), now()])
                ->count(),

            // Partner link
            'partnerLink' => url()->query('/', ['partner' => Auth::user()->username]),

            // Partner period stats: gross accruals for the period (same metric as the
            // Partner Program page), not the net balance delta — see partnerGrossAccrualSince().
            'weekStats' => ['delta' => $partnerWeekAccrual],
            'monthStats' => ['delta' => $partnerMonthAccrual],
        ]);
    }

    /**
     * Число партнёров по линиям первой пятёрки уровней.
     *
     * Спускаемся вглубь только через незабаненных партнёров; уровни без
     * партнёров обрывают обход. Результат: [уровень => количество].
     *
     * @return array<int, int>
     */
    private function partnersByLine(): array
    {
        $lines = [];
        $front = [Auth::id()];

        for ($lvl = 1; $lvl <= 5; $lvl++) {
            $ids = Partner::whereIn('partner_id', $front)
                ->whereHas('user', fn ($q) => $q->whereNull('banned_at'))
                ->pluck('user_id');

            if ($ids->isEmpty()) {
                break;
            }

            $lines[$lvl] = $ids->count();
            $front = $ids->all();
        }

        return $lines;
    }
}
