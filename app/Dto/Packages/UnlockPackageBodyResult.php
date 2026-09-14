<?php

declare(strict_types=1);

namespace App\Dto\Packages;

use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;

final readonly class UnlockPackageBodyResult
{
    public function __construct(
        public string $uuid,
        public BigDecimal $amount,
        public CarbonInterface $payoutAt,
        public BigDecimal $remainingBody,
    ) {}
}
