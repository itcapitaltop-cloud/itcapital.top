<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageFormPage;
use App\MoonShine\Pages\Summary\SummaryIndexPage;
use App\MoonShine\Resources\DepositResource;
use App\MoonShine\Resources\ItcPackageResource;
use App\MoonShine\Resources\SummaryResource;
use App\MoonShine\Resources\UserResource;
use App\MoonShine\Resources\WithdrawResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param MoonShine $core
     * @param MoonShineConfigurator $config
     */
    public function boot(CoreContract $core, ConfiguratorContract $config): void
    {
        $core
            ->resources([
                SummaryResource::class,
                UserResource::class,
                DepositResource::class,
                WithdrawResource::class,
                ItcPackageResource::class,
            ])
            ->pages([
                ...$config->getPages(),
                SummaryIndexPage::class,
                ItcPackageDepositProfitPage::class,
                ItcPackageFormPage::class,
            ]);

        // Добавление кастомных JS и CSS
        moonshineAssets()->add([
            Css::make('/vendor/moonshine/css/moonshine-overrides.css'),
            Js::make('/vendor/moonshine/js/multi-sort.js'),
            Js::make('/vendor/moonshine/js/copy-tooltip.js'),
            Js::make('/vendor/moonshine/js/moonshine-auto-click-itc-packages.js'),
            Js::make('/vendor/moonshine/js/paginator-top.js'),
            Js::make('/vendor/moonshine/js/show-eye-in-password-field.js'),
            Js::make('/vendor/moonshine/js/filters-search-preserver.js'),
            Js::make('/vendor/moonshine/js/show-overflow-popup.js'),
            Js::make('/vendor/moonshine/js/paginator-trim.js'),
            Js::make('/vendor/moonshine/js/flex-top-content.js'),
            Js::make('/vendor/moonshine/js/set-names-for-override-percents-fields.js'),
            Js::make('/vendor/moonshine/js/set-names-for-requirements-fields.js'),
            Js::make('/vendor/moonshine/js/set-names-for-common-percents-fields.js'),
        ]);
    }
}
