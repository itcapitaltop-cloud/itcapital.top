<?php

use App\Helpers\AssetHelper;
use App\Models\Transaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

if (! function_exists('vite')) {
    function vite()
    {
        return new AssetHelper();
    }
}

if (! function_exists('scaleDecimal')) {
    function scaleDecimal(string $decimal, int $scale = 4)
    {
        return BigDecimal::of($decimal)->toScale($scale, RoundingMode::HALF_EVEN);
    }
}

if (! function_exists('scale')) {
    function scale(string $decimal, int $scale = 2)
    {
        return BigDecimal::of($decimal)->toScale($scale, RoundingMode::HALF_EVEN);
    }
}

if (! function_exists('hasPackage')) {
    function hasPackage(?int $userId = null): bool
    {
        if (! $userId) {
            return false;
        }

        /** @var array<int, bool> $cache */
        static $cache = [];

        return $cache[$userId] ??= Transaction::query()
            ->where('user_id', $userId)
            ->whereHas('itcPackage')
            ->exists();
    }
}
