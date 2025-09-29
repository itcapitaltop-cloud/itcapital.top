<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageFormPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageIndexPage;
use App\MoonShine\Pages\User\UserDetailPage;
use App\MoonShine\Resources\DepositResource;
use App\MoonShine\Resources\ItcPackageResource;
use App\MoonShine\Resources\UserResource;
use App\MoonShine\Resources\WithdrawResource;
use App\MoonShine\Resources\SummaryResource;
use MoonShine\Providers\MoonShineApplicationServiceProvider;
use MoonShine\Menu\MenuItem;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Menu\MenuElement;
use MoonShine\Pages\Page;
use Closure;

class MoonShineServiceProvider extends MoonShineApplicationServiceProvider
{

    public function register(): void
    {
        parent::register();

        // указываем, что "домашняя" страница — это UserResource
        moonshine()->home(SummaryResource::class);
    }

    /**
     * @return list<ResourceContract>
     */
    protected function resources(): array
    {
        return [];
    }

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            ItcPackageDepositProfitPage::make('Начисление прибыли'),
            ItcPackageFormPage::make('Редактирование пакета'),
        ];
    }

    /**
     * @return Closure|list<MenuElement>
     */
    protected function menu(): array
    {
        return [
            MenuItem::make('Сводка', new SummaryResource),
            MenuItem::make('Пользователи', new UserResource),
            MenuItem::make('Ввод', new DepositResource),
            MenuItem::make('Выводы', new WithdrawResource),
            MenuItem::make('Пакеты', new ItcPackageResource),
        ];
    }

    /**
     * @return Closure|array{css: string, colors: array, darkColors: array}
     */
    protected function theme(): array
    {
        return [];
    }

    public function boot(): void
    {
        parent::boot();

        moonShineAssets()->add([
            '/vendor/moonshine/css/moonshine-overrides.css',
            '/vendor/moonshine/js/multi-sort.js',
            '/vendor/moonshine/js/copy-tooltip.js',
            '/vendor/moonshine/js/moonshine-auto-click-itc-packages.js',
            '/vendor/moonshine/js/paginator-top.js',
            '/vendor/moonshine/js/show-eye-in-password-field.js',
            '/vendor/moonshine/js/filters-search-preserver.js',
            '/vendor/moonshine/js/show-overflow-popup.js',
            '/vendor/moonshine/js/paginator-trim.js',
            '/vendor/moonshine/js/flex-top-content.js',
            '/vendor/moonshine/js/set-names-for-override-percents-fields.js',
            '/vendor/moonshine/js/set-names-for-requirements-fields.js',
            '/vendor/moonshine/js/set-names-for-common-percents-fields.js',
        ]);
    }
}
