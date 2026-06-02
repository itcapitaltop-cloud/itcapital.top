<?php

declare(strict_types=1);

namespace App\Dto\Finance;

final readonly class WithdrawDataTransferObject
{
    public function __construct(
        public int $paymentSources,
        public string $amount,
        public string $walletAddress,
        public string $commission,
        public string $phone,
        public string $nameBank,
        public string $fullname,
    ) {
        // ..
    }
}
