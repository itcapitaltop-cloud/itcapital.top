<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\BusinessActivity;
use App\MoonShine\Pages\GlobalAdminActivity\GlobalAdminActivityIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Fields\DateRange;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<BusinessActivity>
 */
class GlobalAdminActivityResource extends ModelResource
{
    protected string $model = BusinessActivity::class;

    protected string $title = 'Журнал действий администратора';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            GlobalAdminActivityIndexPage::make($this->title()),
        ];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Дата', 'activity_log.created_at')
                ->fromTo('activity_log.created_at', 'activity_log.created_at'),
        ];
    }

    public function query(): Builder
    {
        return parent::query()
            ->whereJsonContains('properties->feeds', ActivityFeedTypeEnum::GlobalAdmin->value)
            ->with('causer')
            ->latest('activity_log.created_at')
            ->latest('activity_log.id');
    }

    /**
     * @param BusinessActivity $item
     * @return array<string, string[]|string>
     */
    public function rules(Model $item): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function export(): ?ExportHandler
    {
        return ExportHandler::make('Экспортировать')
            ->disk('public')
            ->filename('global-admin-activity-' . now()->format('Ymd-His'));
    }
}
