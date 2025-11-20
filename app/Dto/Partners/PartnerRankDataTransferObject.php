<?php

declare(strict_types=1);

namespace App\Dto\Partners;

use App\Abstracts\DataTransferObject;
use Illuminate\Support\Collection;

final readonly class PartnerRankDataTransferObject extends DataTransferObject
{
    /**
     * @param int $rank
     * @param string $bonus
     * @param \Illuminate\Support\Collection $requirements <int, PartnerRequirementDataTransferObject>
     */
    public function __construct(
        public int $rank,
        public string $bonus,
        public Collection $requirements,
    ) {
        // ..
    }
}
