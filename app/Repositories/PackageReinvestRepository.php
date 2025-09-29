<?php

namespace App\Repositories;

use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Packages\CreatePackageReinvestDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageReinvest;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PackageReinvestRepository implements PackageReinvestRepositoryContract
{

    public function store(CreatePackageReinvestDto $dto): PackageReinvest
    {
//        Log::channel('source')->debug('Creating packageReinvest');
        return PackageReinvest::query()->create([
            'uuid' => 'ITCR-' . Str::random(10),
            'package_uuid' => $dto->packageUuid,
            'expire' => $dto->expire
        ]);
    }

    public function withdraw(string $reinvestUuid, TransactionRepositoryContract $transactionRepo): void
    {
        DB::transaction(function () use ($reinvestUuid, $transactionRepo) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $reinvest = PackageProfitReinvest::query()
                ->where('uuid', $reinvestUuid)
                ->firstOrFail();

            $amount = BigDecimal::of($reinvest->amount);

            $package = ItcPackage::query()
                ->where('uuid', $reinvest->package_uuid)
                ->with('transaction')
                ->firstOrFail();

            $userId = $package->transaction->user_id;

            $transaction = $transactionRepo->commonStore(new CreateTransactionDto(
                userId:      $userId,
                trxType:     TrxTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT,
                balanceType: BalanceTypeEnum::MAIN,
                amount:      $amount,
                acceptedAt:  Carbon::now(),
                prefix:      'WPRP-',
            ));

            PackageProfitReinvestWithdraw::query()->create([
                'uuid'           => $transaction->uuid,
                'reinvest_uuid'  => $reinvestUuid,
            ]);

            app(LogRepositoryContract::class)->updated(
                $transaction,
                'withdraw_package_reinvest_profit',
                ['reinvest_amount'   => $reinvest->amount],
                ['withdrawn_amount'  => (string) $amount],
                $userId
            );
        });
    }
}
