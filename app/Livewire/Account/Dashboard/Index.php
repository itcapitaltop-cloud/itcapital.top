<?php

namespace App\Livewire\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
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

        // Обход партнёрского дерева (до 5 уровней) считаем один раз:
        // отсюда получаем и распределение по линиям, и общее число партнёров.
        $partnersInLines = $this->partnersByLine();

        return view('livewire.account.dashboard.index', [

            'packagesCount' => ItcPackage::query()
                ->whereHas('transaction.user', fn ($query) => $query->whereId(auth()->id()))
                ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING])
                ->count(),

            'depositTotalAmount' => ItcPackage::query()
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('user_id', Auth::id())
                ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING])
                ->where(function ($q) {
                    $q->whereNotIn('type', [PackageTypeEnum::PRESENT, PackageTypeEnum::STAKING])
                        ->orWhereDoesntHave('zeroing');
                })
                ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->get()
                ->sum(fn ($p) => (float) $p->transaction->amount + (float) $p->reinvest_profits_sum_amount + (float) $p->partner_transfers_sum_amount),

            'transactions' => Transaction::query()
                ->with([
                    'itcPackage' => fn ($query) => $query->whereNotIn('type', [PackageTypeEnum::STAKING]),
                ])
                ->where('user_id', Auth::id())
                ->whereNot('trx_type', TrxTypeEnum::HIDDEN_DEPOSIT)
                ->orderByDesc('created_at')
                ->get(),

            'partners' => Partner::with(['user' => fn ($q) => $q->withoutGlobalScope('notBanned')])
                ->where('partner_id', Auth::id())
                ->whereHas('user', fn ($q) => $q->whereNull('banned_at'))
                ->limit(5)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Partner $p) => $p->user),

            // доход по пакетам: вся история и последняя неделя
            'yieldTotal' => PackageProfit::query()
                ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', Auth::id())
                ->whereNotIn('itc_packages.type', [PackageTypeEnum::PRESENT, PackageTypeEnum::STAKING])
                ->sum('package_profits.amount'),

            'yieldWeek' => PackageProfit::query()
                ->join('itc_packages', 'package_profits.package_uuid', '=', 'itc_packages.uuid')
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', Auth::id())
                ->whereNotIn('itc_packages.type', [PackageTypeEnum::PRESENT, PackageTypeEnum::STAKING])
                ->where('package_profits.created_at', '>=', now()->subWeek())
                ->sum('package_profits.amount'),

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

            // Partner period stats
            'weekStats' => $transactionRepo->partnerPeriodStats(now()->subWeek()),
            'monthStats' => $transactionRepo->partnerPeriodStats(now()->subMonth()),
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
