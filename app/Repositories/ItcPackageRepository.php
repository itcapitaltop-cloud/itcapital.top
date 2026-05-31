<?php

namespace App\Repositories;

use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\ItcPackage;
use App\Models\PackagePartnerTransfer;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitWithdraw;
use App\Models\PartnerRank;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                function (Transaction $trx) use ($packageData) {
                    $package = ItcPackage::create($packageData + ['uuid' => $trx->uuid]);

                    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                        type: ActivityEventTypeEnum::PackagePurchased,
                        userId: $trx->user_id,
                        subject: $package,
                        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $trx->amount,
                            'package_uuid' => $package->uuid,
                            'package_type' => $package->type->value,
                            'package_definition_id' => $package->package_definition_id,
                        ],
                        causer: auth()->user(),
                        logName: 'packages',
                        context: auth()->check() ? 'admin' : 'system',
                    ));

                    return $package;
                }
            )
            : $transactionRepo->checkBalanceAndStore(
                $dto,
                function (Transaction $trx) use ($packageData) {
                    $package = ItcPackage::create($packageData + ['uuid' => $trx->uuid]);

                    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                        type: ActivityEventTypeEnum::PackagePurchased,
                        userId: $trx->user_id,
                        subject: $package,
                        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $trx->amount,
                            'package_uuid' => $package->uuid,
                            'package_type' => $package->type->value,
                            'package_definition_id' => $package->package_definition_id,
                        ],
                        causer: auth()->user(),
                        logName: 'packages',
                        context: auth()->check() ? 'admin' : 'system',
                    ));

                    return $package;
                }
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
            $oldType = $package->type;
            $oldAmount = $package->transaction->amount;

            $amount = BigDecimal::of($package->transaction->amount);

            if (
                $package->type !== PackageTypeEnum::PRESENT
                && $amount->isPositive()
            ) {
                $transactionRepo->commonStore(new CreateTransactionDto(
                    userId: $userId,
                    trxType: TrxTypeEnum::WITHDRAW_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount: $amount,
                    acceptedAt: Carbon::now(),
                    prefix: 'WPC-',
                ));
            }

            foreach ($package->reinvestProfits as $reinvest) {
                $reinvestRepo->withdraw($reinvest->uuid, $transactionRepo, false);
            }

            $profit = $this->getCurrentProfitAmountByPackageUuid($uuid);

            if ($package->type !== PackageTypeEnum::STAKING && $profit->isPositive()) {
                $trx = $transactionRepo->commonStore(new CreateTransactionDto(
                    userId: $userId,
                    trxType: TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount: $profit,
                    acceptedAt: Carbon::now(),
                    prefix: 'WPP-',
                ));

                PackageProfitWithdraw::query()->create([
                    'uuid' => $trx->uuid,
                    'package_uuid' => $uuid,
                ]);
            }

            $package->transaction->save();
            $package->type = PackageTypeEnum::ARCHIVE;
            $package->save();

            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PackageClosed,
                userId: $userId,
                subject: $package,
                feeds: $oldType === PackageTypeEnum::STAKING
                    ? [ActivityFeedTypeEnum::Staking, ActivityFeedTypeEnum::UserDetailUser]
                    : [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => (string) $amount,
                    'package_uuid' => $package->uuid,
                    'package_type' => $oldType->value,
                    'new_package_type' => $package->type->value,
                ],
                causer: auth()->user(),
                logName: 'packages',
                context: auth()->check() ? 'admin' : 'system',
            ));

            app(LogRepositoryContract::class)->updated(
                $package,
                'close_itc_package',
                [
                    'old_type' => $oldType->value,
                    'old_amount' => (string) $oldAmount,
                    'old_user_id' => $userId,
                ],
                [
                    'new_type' => PackageTypeEnum::ARCHIVE->value,
                    'new_amount' => (string) $package->transaction->amount,
                    'new_user_id' => $userId,
                ],
                $userId
            );
        });
    }

    public function partnerTransferToPackage(
        int $userId,
        string $packageUuid,
        float $amount,
        TransactionRepositoryContract $trxRepo
    ): void {
        $trxRepo->checkBalanceAndStore(
            new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::PARTNER_TO_PACKAGE,
                balanceType: BalanceTypeEnum::PARTNER,
                amount: $amount,
                acceptedAt: now(),
                prefix: 'PP-',
            ),
            function (Transaction $trx) use ($packageUuid) {
                PackagePartnerTransfer::create([
                    'uuid' => $trx->uuid,
                    'package_uuid' => $packageUuid,
                ]);
            }
        );
    }

    /**
     * @param \App\Models\User $user
     * @return float
     */
    public function personalDepositToPackage(User $user): float
    {
        $baseQuery = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $user->id)
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->whereNull('itc_packages.closed_at')
            ->with('transaction:id,uuid,amount,accepted_at,user_id');

        $allPersonal = (clone $baseQuery)
            ->withSum('reinvestProfitsAll', 'amount')
            ->get()
            ->sum(fn ($p) => (float) $p->transaction->amount +
                (float) $p->reinvest_profits_all_sum_amount
            );

        if ($user->overridden_rank) {
            $baseRank = PartnerRank::with('requirements')
                ->where('rank', $user->rank)
                ->first();

            if (is_null($baseRank)) {
                return $allPersonal;
            }

            $personalMin = (float) ($baseRank->requirements->whereNull('line')->first()?->personal_deposit ?? 0);

            $fromDate = $user->overridden_rank_from;

            if ($allPersonal < $personalMin) {
                $sinceSum = (clone $baseQuery)
                    ->withSum([
                        'reinvestProfitsAll' => function ($q) use ($fromDate) {
                            $q->when($fromDate, fn ($qq) => $qq->where('created_at', '>=', $fromDate));
                        },
                    ], 'amount')
                    ->get()
                    ->sum(function ($p) use ($fromDate) {
                        $buy = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                            ? (float) $p->transaction->amount
                            : 0.0;

                        return $buy + (float) $p->reinvest_profits_all_sum_amount;
                    });

                return $personalMin + $sinceSum;
            }
        }

        return $allPersonal;

    }

    /**
     * @param \App\Models\User $user
     * @param \DateTime|null $fromDate
     * @return float
     */
    public function personalDepositSince(User $user, ?\DateTime $fromDate): float
    {
        if (! $fromDate) {
            return 0.0;
        }

        $baseQuery = $this->getActivePackagesQuery($user->id);

        return $baseQuery
            ->withSum([
                'reinvestProfitsAll' => function ($q) use ($fromDate) {
                    $q->where('created_at', '>=', $fromDate);
                },
            ], 'amount')
            ->get()
            ->sum(function ($p) use ($fromDate) {
                $buyAmount = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                    ? (float) $p->transaction->amount
                    : 0.0;

                return $buyAmount + (float) $p->reinvest_profits_all_sum_amount;
            });
    }

    /**
     * @param \Illuminate\Support\Collection $userIds
     * @param \DateTime|null $fromDate
     * @return float
     */
    public function reinvestAmountForUsers(Collection $userIds, ?\DateTime $fromDate = null): float
    {
        $query = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->whereIn('transactions.user_id', $userIds);

        return (float) $query
            ->withSum([
                'reinvestProfitsAll' => function ($q) use ($fromDate) {
                    if ($fromDate) {
                        $q->where('created_at', '>=', $fromDate);
                    }
                },
            ], 'amount')
            ->get()
            ->sum('reinvest_profits_all_sum_amount');
    }

    /**
     * Базовый запрос для активных пакетов пользователя
     */
    private function getActivePackagesQuery(int $userId): Builder
    {
        return ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $userId)
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->whereNull('itc_packages.closed_at')
            ->with('transaction:id,uuid,amount,accepted_at,user_id');
    }
}
