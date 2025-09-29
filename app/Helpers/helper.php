<?php

use App\Helpers\AssetHelper;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

if (!function_exists('vite')) {
    function vite()
    {
        return new AssetHelper;
    }
}

if (!function_exists('scaleDecimal')) {
    function scaleDecimal(string $decimal, int $scale = 4)
    {
        return BigDecimal::of($decimal)->toScale($scale, RoundingMode::HALF_EVEN);
    }
}

if (!function_exists('scale')) {
    function scale(string $decimal, int $scale = 2)
    {
        return BigDecimal::of($decimal)->toScale($scale, RoundingMode::HALF_EVEN);
    }
}
