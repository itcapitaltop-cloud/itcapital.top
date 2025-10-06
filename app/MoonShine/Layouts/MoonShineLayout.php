<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Resources\DepositResource;
use App\MoonShine\Resources\ItcPackageResource;
use App\MoonShine\Resources\SummaryResource;
use App\MoonShine\Resources\UserResource;
use App\MoonShine\Resources\WithdrawResource;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\MenuManager\MenuItem;

final class MoonShineLayout extends AppLayout
{
    protected function menu(): array
    {
        return [
            MenuItem::make('Сводка', SummaryResource::class),
            MenuItem::make('Пользователи', UserResource::class),
            MenuItem::make('Депозиты', DepositResource::class),
            MenuItem::make('Выводы', WithdrawResource::class),
            MenuItem::make('Пакеты', ItcPackageResource::class),
        ];
    }
}
