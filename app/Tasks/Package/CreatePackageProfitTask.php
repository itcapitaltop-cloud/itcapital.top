<?php

declare(strict_types=1);

namespace App\Tasks\Package;

use App\Models\PackageProfit;
use Illuminate\Support\Str;

final class CreatePackageProfitTask
{
    public function run(string $packageUuid, float $amount): PackageProfit
    {
        return PackageProfit::create([
            'uuid' => 'PP-' . Str::random(10),
            'package_uuid' => $packageUuid,
            'amount' => $amount,
        ]);
    }
}
