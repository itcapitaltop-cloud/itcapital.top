<?php
declare(strict_types=1);

namespace App\Dto\Transactions;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use Carbon\Carbon;

final readonly class CreateTransactionDto
{
    /**
     * @param int $userId
     * @param \App\Enums\Transactions\TrxTypeEnum $trxType
     * @param \App\Enums\Transactions\BalanceTypeEnum $balanceType
     * @param string $amount
     * @param string|\Carbon\Carbon|null $acceptedAt
     * @param string|\Carbon\Carbon|null $rejectedAt
     * @param string $prefix
     */
    public function __construct(
        public int $userId,
        public TrxTypeEnum $trxType,
        public BalanceTypeEnum $balanceType,
        public string $amount,
        public string|Carbon|null $acceptedAt = null,
        public string|Carbon|null $rejectedAt = null,
        public string $prefix = ''
    ) {
        // ..
    }
}
