<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\GlobalAdminActivity;

use App\Models\BusinessActivity;
use App\Services\ActivityLog\ActivityFeedService;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;

class GlobalAdminActivityIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        $feedService = app(ActivityFeedService::class);

        return [
            Text::make('Событие', formatted: fn (BusinessActivity $item): string => $feedService->globalAdminFeedRow($item)['action'])
                ->showOnExport(),
            Text::make('Администратор', formatted: fn (BusinessActivity $item): string => $feedService->globalAdminFeedRow($item)['admin_login'])
                ->showOnExport(),
            Text::make('Старые значения', formatted: fn (BusinessActivity $item): string => $feedService->globalAdminFeedRow($item)['old_values'])
                ->showOnExport(),
            Text::make('Новые значения', formatted: fn (BusinessActivity $item): string => $feedService->globalAdminFeedRow($item)['new_values'])
                ->showOnExport(),
            Text::make('Дата', formatted: fn (BusinessActivity $item): string => $feedService->globalAdminFeedRow($item)['date'])
                ->sortable(function ($query, string $column, string $direction) {
                    return $query->orderBy('activity_log.created_at', $direction);
                })
                ->showOnExport(),
        ];
    }
}
