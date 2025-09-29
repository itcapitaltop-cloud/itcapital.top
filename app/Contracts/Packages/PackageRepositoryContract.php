<?php

namespace App\Contracts\Packages;

interface PackageRepositoryContract
{
    public function getToWithdrawProfitByPackageUuid(string $uuid): string;
}
