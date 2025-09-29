<?php

namespace App\Contracts\Packages;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use Brick\Math\BigDecimal;
use Closure;

interface ItcPackageRepositoryContract
{
    public function getCurrentProfitAmountByPackageUuid(string $uuid): BigDecimal;
    public function whenCurrentProfitAmountIsPositive(string $uuid, Closure $callback): void;

    public function closePackage(
        string $uuid,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo
    );

    public function createPackage(
        CreateTransactionDto $dto,
        array $packageData,
        TransactionRepositoryContract $transactionRepo,
        bool $skipBalance = false
    ): mixed;

}
