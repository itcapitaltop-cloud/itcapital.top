<?php

namespace App\Dto\Transactions;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use Carbon\Carbon;

readonly class CreateTransactionDto
{
    public function __construct(
        public int                $userId,
        public TrxTypeEnum        $trxType,
        public BalanceTypeEnum    $balanceType,
        public string             $amount,
        public string|Carbon|null $acceptedAt = null,
        public string|Carbon|null $rejectedAt = null,
        public string             $prefix = ''
    ) {
    }
}
