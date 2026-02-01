<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\PackageProfit;
use App\MoonShine\Pages\ActivityLog\ActivityLogIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Fields\DateRange;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<PackageProfit>
 */
class ActivityLogResource extends ModelResource
{
    protected string $model = PackageProfit::class;

    protected string $title = 'Журнал начислений';

    /**
     * Поиск по нику и UUID пакета
     */
    public function search(): array
    {
        return ['package_uuid'];
    }

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            ActivityLogIndexPage::make($this->title()),
        ];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Дата начисления', 'created_at')
                ->default([
                    now()->subWeek()->toDateString(),
                    now()->toDateString(),
                ]),
        ];
    }

    public function query(): Builder
    {
        return parent::query()
            ->with(['package.transaction.user'])
            ->where('created_at', '>=', now()->subMonth())
            ->when(
                ! request()->filled('filters.created_at'),
                fn ($q) => $q->where('created_at', '>=', now()->subWeek())
            )
            ->latest('created_at');
    }

    /**
     * @param PackageProfit $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
