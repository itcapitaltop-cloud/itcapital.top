<?php

declare(strict_types=1);

namespace App\Actions\Staking;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use LeMaX10\SimpleActions\Action;

final class CalculateUserStakingSumAction extends Action
{
    protected function handle(int $userId)
    {
        $packages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->with(['transaction' => fn ($query) => $query->where('user_id', $userId)])
            ->withSum('transaction', 'amount')
            ->get();

        return $packages;
    }
}
