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
            DateRange::make('Дата начисления', 'package_profits.created_at')
                ->fromTo('package_profits.created_at', 'package_profits.created_at'),
        ];
    }

    public function query(): Builder
    {
        $query = parent::query()
            ->select('package_profits.*')
            ->leftJoin('itc_packages as pkg', 'package_profits.package_uuid', '=', 'pkg.uuid')
            ->leftJoin('transactions as tr', 'pkg.uuid', '=', 'tr.uuid')
            ->leftJoin('users as u', 'tr.user_id', '=', 'u.id')
            ->with(['package.transaction.user'])
            ->where('package_profits.created_at', '>=', now()->subMonth());

        if (! request()->filled('filters.created_at')) {
            $query->where('package_profits.created_at', '>=', now()->subWeek(2));
        }

        return $query->latest('package_profits.created_at');
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

    /**
     * @return string[]
     */
    public function getActiveActions(): array
    {
        return ['view'];
    }
}
