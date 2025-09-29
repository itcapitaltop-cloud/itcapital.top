<?php

namespace App\Contracts\Accruals;

interface StartBonusAccrualContract {
    public function accrue(int $buyerId, float $packageAmount): void;
}
