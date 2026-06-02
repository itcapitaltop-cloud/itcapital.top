<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): float
    {
        return $value / 100;
    }

    public function set($model, string $key, $value, array $attributes): int
    {
        return (int) round($value * 100);
    }
}
