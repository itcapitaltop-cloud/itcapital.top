<?php

namespace App\Repositories;

use App\Contracts\Packages\PackageRepositoryContract;
use App\Models\PackageProfit;
use App\Models\PackageProfitWithdraw;
use Illuminate\Support\Facades\DB;

class PackageRepository implements PackageRepositoryContract
{
    public function getToWithdrawProfitByPackageUuid(string $uuid): string
    {
        return DB::table(PackageProfit::query()
            ->where('package_uuid', $uuid)
            ->select('amount')
            ->unionAll(
                PackageProfitWithdraw::query()
                    ->join('transactions', 'package_profit_withdraws.uuid', '=', 'transactions.uuid')
                    ->where('package_uuid', $uuid)
                    ->selectRaw('(-1 * amount) as amount')
            ), 'profits')
            ->sum('amount');
    }
}
