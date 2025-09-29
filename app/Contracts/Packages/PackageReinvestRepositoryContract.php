<?php

namespace App\Contracts\Packages;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Packages\CreatePackageReinvestDto;
use App\Models\PackageReinvest;

interface PackageReinvestRepositoryContract
{
    public function store(CreatePackageReinvestDto $dto): PackageReinvest;

    public function withdraw(string $reinvestUuid, TransactionRepositoryContract $transactionRepo): void;
}
