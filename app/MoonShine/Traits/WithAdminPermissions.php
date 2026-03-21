<?php

declare(strict_types=1);

namespace App\MoonShine\Traits;

use App\MoonShine\Components\AdminPermissions;
use Illuminate\Support\Facades\Route;
use MoonShine\Enums\Layer;
use MoonShine\Enums\PageType;
use MoonShine\Permissions\Http\Controllers\PermissionController;

trait WithAdminPermissions
{
    protected function bootWithAdminPermissions(): void
    {
        $this->getPages()
            ->findByType(PageType::FORM)
            ?->pushToLayer(
                Layer::BOTTOM,
                AdminPermissions::make('Permissions', $this)
            );
    }

    protected function resolveRoutes(): void
    {
        parent::resolveRoutes();

        Route::post(
            'permissions/{resourceItem}',
            PermissionController::class
        )->name('permissions');
    }
}
