<?php

declare(strict_types=1);

namespace App\Dto\Finance;

final readonly class DepositDataTransferObject
{
    public function __construct(
        public int $paymentSources,
        public string $amount,
        public string $walletAddress,
        public string $transactionHash,
    ) {
        // ..
    }
}
