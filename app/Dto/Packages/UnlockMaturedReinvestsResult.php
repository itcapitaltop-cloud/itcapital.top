<?php

declare(strict_types=1);

namespace App\Dto\Packages;

use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;

final readonly class UnlockMaturedReinvestsResult
{
    public function __construct(
        public BigDecimal $amount,
        public CarbonInterface $payoutAt,
        public int $count,
    ) {}
}
