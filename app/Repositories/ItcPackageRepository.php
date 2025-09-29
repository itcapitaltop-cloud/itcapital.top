<?php

namespace App\Repositories;

use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\ItcPackage;
use App\Models\PackagePartnerTransfer;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitWithdraw;
use App\Models\Transaction;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItcPackageRepository implements ItcPackageRepositoryContract
{
    public function getCurrentProfitAmountByPackageUuid(string $uuid): BigDecimal
    {
        $reinvestsQuery = PackageProfitReinvest::query()
            ->where('package_uuid', $uuid)
            ->selectRaw('(-1 * amount) as amount');

        $withdrawsQuery = PackageProfitWithdraw::query()
            ->where('package_uuid', $uuid)
            ->join('transactions', 'package_profit_withdraws.uuid', '=', 'transactions.uuid')
            ->selectRaw('(-1 * amount) as amount');

        $query = PackageProfit::query()
            ->where('package_uuid', $uuid)
            ->unionAll($reinvestsQuery)
            ->unionAll($withdrawsQuery)
            ->select('amount');

        return BigDecimal::of(DB::table($query, 'profits')->sum('amount'));
    }

    public function whenCurrentProfitAmountIsPositive(string $uuid, Closure $callback): void
    {
        DB::transaction(function () use ($uuid, $callback) {
            DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $amount = $this->getCurrentProfitAmountByPackageUuid($uuid);

            if ($amount->isPositive()) {
                $callback($amount);
                return;
            }

            throw new InvalidAmountException('Недостаточно средств для выполнения операции');
        });
    }

    public function createPackage(
        CreateTransactionDto $dto,
        array $packageData,
        TransactionRepositoryContract $transactionRepo,
        bool $skipBalance = false
    ): mixed {
        return $skipBalance
            ? $transactionRepo->store(
                $dto,
                fn (Transaction $trx) => ItcPackage::create($packageData + ['uuid' => $trx->uuid])
            )
            : $transactionRepo->checkBalanceAndStore(
                $dto,
                fn (Transaction $trx) => ItcPackage::create($packageData + ['uuid' => $trx->uuid])
            );
    }

    public function closePackage(
        string $uuid,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo
    ): void {
        DB::transaction(function () use ($uuid, $transactionRepo, $reinvestRepo) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $package = ItcPackage::query()
                ->where('uuid', $uuid)
                ->with([
                    'transaction',
                    'reinvestProfits' => fn ($q) => $q->whereDoesntHave('withdraw'),
                ])
                ->firstOrFail();

            $userId = $package->transaction->user_id;

            $amount = BigDecimal::of($package->transaction->amount);
            if (
                $package->type !== PackageTypeEnum::PRESENT
                && $amount->isPositive()
            ) {
                $transactionRepo->commonStore(new CreateTransactionDto(
                    userId:      $userId,
                    trxType:     TrxTypeEnum::WITHDRAW_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount:      $amount,
                    acceptedAt:  Carbon::now(),
                    prefix:      'WPC-',
                ));
            }

            foreach ($package->reinvestProfits as $reinvest) {
                $reinvestRepo->withdraw($reinvest->uuid, $transactionRepo);
            }

            $profit = $this->getCurrentProfitAmountByPackageUuid($uuid);
            if ($profit->isPositive()) {
                $trx = $transactionRepo->commonStore(new CreateTransactionDto(
                    userId:      $userId,
                    trxType:     TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount:      $profit,
                    acceptedAt:  Carbon::now(),
                    prefix:      'WPP-',
                ));

                PackageProfitWithdraw::query()->create([
                    'uuid'          => $trx->uuid,
                    'package_uuid'  => $uuid,
                ]);
            }

            $package->transaction->save();
            $package->type = PackageTypeEnum::ARCHIVE;
            $package->save();

            app(LogRepositoryContract::class)->updated(
                $package,
                'close_itc_package',
                [
                    'old_type'   => $package->getOriginal('type'),
                    'old_amount' => $package->transaction->getOriginal('amount'),
                    'old_user_id' => $userId,
                ],
                [
                    'new_type'   => PackageTypeEnum::ARCHIVE,
                    'new_amount' => $package->transaction->amount,
                    'new_user_id' => $userId,
                ],
                $userId
            );
        });
    }

    public function partnerTransferToPackage(
        int    $userId,
        string $packageUuid,
        float  $amount,
        TransactionRepositoryContract $trxRepo
    ): void
    {
        $trxRepo->checkBalanceAndStore(
            new CreateTransactionDto(
                userId:      $userId,
                trxType:     TrxTypeEnum::PARTNER_TO_PACKAGE,
                balanceType: BalanceTypeEnum::PARTNER,
                amount:      $amount,
                acceptedAt:  now(),
                prefix:      'PP-',
            ),
            function (Transaction $trx) use ($packageUuid) {
                PackagePartnerTransfer::create([
                    'uuid'         => $trx->uuid,
                    'package_uuid' => $packageUuid,
                ]);
            }
        );
    }
}
