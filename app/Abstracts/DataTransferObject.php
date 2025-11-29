<?php

declare(strict_types=1);

namespace App\Abstracts;

abstract readonly class DataTransferObject
{
    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
