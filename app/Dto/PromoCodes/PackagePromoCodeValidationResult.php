<?php

declare(strict_types=1);

namespace App\Dto\PromoCodes;

use App\Models\PromoCode;

final readonly class PackagePromoCodeValidationResult
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public ?PromoCode $promoCode,
        public string $effectiveMinimumAmount,
        public ?string $errorCode = null,
        public array $context = [],
    ) {}

    public function isValid(): bool
    {
        return $this->errorCode === null;
    }
}
