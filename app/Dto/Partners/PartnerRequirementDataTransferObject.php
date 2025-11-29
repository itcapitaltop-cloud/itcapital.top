<?php

declare(strict_types=1);

namespace App\Dto\Partners;

use App\Abstracts\DataTransferObject;

final readonly class PartnerRequirementDataTransferObject extends DataTransferObject
{
    /**
     * @param int|null $line
     * @param string|null $requiredTurnover
     * @param string|null $deposit
     */
    public function __construct(
        public ?int $line = null,
        public ?string $requiredTurnover = null,
        public ?string $deposit = null,
    ) {
        // ..
    }
}
