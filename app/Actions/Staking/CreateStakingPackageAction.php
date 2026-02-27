<?php

declare(strict_types=1);

namespace App\Actions\Staking;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Tasks\Transaction\CreateTransactionTask;
use LeMaX10\SimpleActions\Action;

final class CreateStakingPackageAction extends Action
{
    protected function handle(int $userId, float $amount, float $monthProfitPercent = 2.0): ItcPackage
    {
        $transaction = new CreateTransactionTask()->acceptedAt(now())->run($amount, $userId);

        return ItcPackage::query()
            ->create([
                'uuid' => $transaction->uuid,
                'work_to' => now()->addYears(3), // Пакет бессрочный, но предыдущий разраб дебил сделал поле обязательным :)
                'month_profit_percent' => $monthProfitPercent,
                'type' => PackageTypeEnum::STAKING,
            ]);
    }
}
