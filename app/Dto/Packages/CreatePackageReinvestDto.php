<?php

namespace App\Dto\Packages;

use Illuminate\Support\Carbon;

readonly class CreatePackageReinvestDto
{
    public function __construct(
        public string $packageUuid,
        public Carbon $expire
    )
    {
    }
}
