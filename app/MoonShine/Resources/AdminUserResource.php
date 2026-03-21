<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\MoonShine\Traits\WithAdminPermissions;
use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Resources\MoonShineUserResource as BaseMoonShineUserResource;

class AdminUserResource extends BaseMoonShineUserResource
{
    use WithAdminPermissions;

    public string $model = MoonshineUser::class;

    public function title(): string
    {
        return 'Админы';
    }
}
