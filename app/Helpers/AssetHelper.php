<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

class AssetHelper
{
    public function icon(string $path): string
    {
        return Vite::asset("resources/assets/img/icons/" . Str::trim($path, '/'));
    }

    public function font(string $path): string
    {
        return Vite::asset("resources/assets/fonts/" . Str::trim($path, '/'));
    }

    public function img(string $path): string
    {
        return Vite::asset("resources/assets/img/" . Str::trim($path, '/'));
    }

    public function academy(string $path): string
    {
        return Vite::asset('resources/academy/img/' . Str::trim($path, '/'));
    }
}
