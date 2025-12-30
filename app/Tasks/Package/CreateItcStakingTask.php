<?php

declare(strict_types=1);

namespace App\Tasks\Package;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Tasks\Transaction\CreateTransactionTask;

final class CreateItcStakingTask
{
    private float $monthProfitPercent = 2.0;

    /**
     * @param string $amount
     * @param int $userId
     * @return \App\Models\ItcPackage
     */
    public function run(string $amount, int $userId): ItcPackage
    {
        $transaction = new CreateTransactionTask()->acceptedAt(now())->run($amount, $userId);

        return ItcPackage::query()->create([
            'uuid' => $transaction->uuid,
            'work_to' => now()->addYears(3), // Пакет бессрочный, но предыдущий разраб дебил сделал поле обязательным :)
            'month_profit_percent' => $this->monthProfitPercent,
            'type' => PackageTypeEnum::STAKING,
        ]);
    }

    /**
     * @param float $percent
     * @return $this
     */
    public function setMothProfitPercent(float $percent): self
    {
        $this->monthProfitPercent = $percent;

        return $this;
    }
}
