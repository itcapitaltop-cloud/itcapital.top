<?php

namespace App\Livewire\Account\CommonFund;

use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Packages\PackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Packages\CreatePackageReinvestDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\Package;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitWithdraw;
use App\Models\PackageReinvest;
use App\Models\PackageWithdraw;
use App\Models\Transaction;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class PackagesList extends Component
{
    public ?Model $selectedPackage = null;

    public function withdraw(string $uuid, TransactionRepositoryContract $transactionRepo, PackageRepositoryContract $packageRepo): void
    {
        $profit = $packageRepo->getToWithdrawProfitByPackageUuid($uuid);
        $transaction = Transaction::query()->where('uuid', $uuid)->first();

        $transactionRepo->store(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
            balanceType: BalanceTypeEnum::MAIN,
            amount: BigDecimal::of($transaction->amount)->plus($profit),
            acceptedAt: Carbon::now(),
            prefix: 'ITCW-'
        ), function (Transaction $trx) use ($uuid) {
            PackageWithdraw::query()->create([
                'uuid' => $trx->uuid,
                'package_uuid' => $uuid
            ]);
        });
    }

    public function withdrawProfit(string $uuid, TransactionRepositoryContract $transactionRepo, PackageRepositoryContract $packageRepo): void
    {
//        Log::channel('source')->debug('withdrawProfitPackagesList');
        $profit = $packageRepo->getToWithdrawProfitByPackageUuid($uuid);

        $transactionRepo->store(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
            balanceType: BalanceTypeEnum::MAIN,
            amount: $profit,
            acceptedAt: Carbon::now(),
            prefix: 'WP-'
        ), function (Transaction $trx) use ($uuid) {
            return PackageProfitWithdraw::query()->create([
                'uuid' => $trx->uuid,
                'package_uuid' => $uuid
            ]);
        });
    }

    public function reinvestProfit(string $packageUuid, PackageRepositoryContract $packageRepo): void
    {
        $profit = $packageRepo->getToWithdrawProfitByPackageUuid($packageUuid);

        PackageProfitReinvest::query()->create([
            'uuid' => 'RP-' . Str::random(10),
            'package_uuid' => $packageUuid,
            'amount' => $profit
        ]);
    }

    public function reinvest(string $packageUuid, PackageReinvestRepositoryContract $packageReinvestRepo): void
    {
        $packageReinvestRepo->store(new CreatePackageReinvestDto(
            packageUuid: $packageUuid,
            expire: Carbon::now()->addWeeks(30)
        ));
    }

    public function render()
    {
        return view('livewire.account.common-fund.packages-list', [
            'packages' => ItcPackage::query()
                        ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                        ->leftJoin('package_withdraws', 'itc_packages.uuid', '=', 'package_withdraws.package_uuid')
                        ->whereNull('package_withdraws.package_uuid')
                        ->where('user_id', Auth::id())
                        ->select('itc_packages.uuid as uuid', 'amount', 'month_profit_percent', 'user_id', 'transactions.created_at as created_at')
                        ->addSelect([
                            'profit' => DB::table(PackageProfit::query()
                                                ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                ->select('amount')
                                                ->unionAll(
                                                    PackageProfitReinvest::query()
                                                        ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                        ->selectRaw('(-1 * amount) as amount')
                                                )
                                                ->unionAll(
                                                    PackageProfitWithdraw::query()
                                                        ->join('transactions', 'package_profit_withdraws.uuid', '=', 'transactions.uuid')
                                                        ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                        ->selectRaw('(-1 * amount) as amount')
                                                ), 'profits')

                                                ->selectRaw('COALESCE(sum(amount), 0)'),
                            'totalProfit' => PackageProfit::query()
                                                ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                ->selectRaw('COALESCE(sum(amount), 0)'),
                            'profitReinvest' => PackageProfitReinvest::query()
                                                ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                ->selectRaw('COALESCE(sum(amount), 0)'),
                            'expire' => PackageReinvest::query()
                                            ->whereColumn('package_uuid', 'itc_packages.uuid')
                                            ->limit(1)
                                            ->orderByDesc('created_at')
                                            ->select('expire'),
                            'reinvestCount' => PackageReinvest::query()
                                                ->whereColumn('package_uuid', 'itc_packages.uuid')
                                                ->selectRaw('count(*) - 1'),
                        ])
                        ->get()
        ]);
    }
}
