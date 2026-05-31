<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\ItcPackage\ItcPackageFormPage;
use App\MoonShine\Resources\ActivityLogResource;
use App\MoonShine\Resources\AdminUserResource;
use App\MoonShine\Resources\DepositResource;
use App\MoonShine\Resources\GlobalAdminActivityResource;
use App\MoonShine\Resources\ItcPackageResource;
use App\MoonShine\Resources\ItcStakingResource;
use App\MoonShine\Resources\NewsResource;
use App\MoonShine\Resources\PackageDefinitionResource;
use App\MoonShine\Resources\PromoCodeResource;
use App\MoonShine\Resources\SummaryResource;
use App\MoonShine\Resources\UserResource;
use App\MoonShine\Resources\VerifyingUserResource;
use App\MoonShine\Resources\WithdrawResource;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Menu\MenuElement;
use MoonShine\Menu\MenuItem;
use MoonShine\Pages\Page;
use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Providers\MoonShineApplicationServiceProvider;
use MoonShine\Resources\MoonShineUserRoleResource;

class MoonShineServiceProvider extends MoonShineApplicationServiceProvider
{
    public function register(): void
    {
        parent::register();

        moonshine()->home(function (): string {
            $user = auth(config('moonshine.auth.guard', 'moonshine'))->user();

            if (! $user instanceof MoonshineUser) {
                return NewsResource::class;
            }

            if ($this->hasExplicitPermissions($user)) {
                if ($user->isHavePermission(NewsResource::class, 'viewAny') && ! $user->isHavePermission(SummaryResource::class, 'viewAny')) {
                    return NewsResource::class;
                }

                if ($user->isHavePermission(SummaryResource::class, 'viewAny')) {
                    return SummaryResource::class;
                }
            }

            if ($user->isSuperUser()) {
                return SummaryResource::class;
            }

            return NewsResource::class;
        });

        moonshine()->defineAuthorization(
            function (ResourceContract $resource, Model $user, string $ability): bool {
                if (! $user instanceof MoonshineUser) {
                    return true;
                }

                if ($this->hasExplicitPermissions($user)) {
                    return $user->isHavePermission($resource::class, $ability);
                }

                if ($user->isSuperUser()) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * @return list<ResourceContract>
     */
    protected function resources(): array
    {
        return [
            new AdminUserResource(),
            new MoonShineUserRoleResource(),
            new ActivityLogResource(),
            new GlobalAdminActivityResource(),
            new PromoCodeResource(),
            new PackageDefinitionResource(),
        ];
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
            MenuItem::make('Сводка', new SummaryResource())
                ->canSee($this->canSeeResource(SummaryResource::class)),
            MenuItem::make('Пользователи', new UserResource())
                ->canSee($this->canSeeResource(UserResource::class)),
            MenuItem::make('Ввод', new DepositResource())
                ->canSee($this->canSeeResource(DepositResource::class)),
            MenuItem::make('Выводы', new WithdrawResource())
                ->canSee($this->canSeeResource(WithdrawResource::class)),
            MenuItem::make('Пакеты', new ItcPackageResource())
                ->canSee($this->canSeeResource(ItcPackageResource::class)),
            MenuItem::make('Настройки пакетов', new PackageDefinitionResource())
                ->canSee($this->canSeeResource(PackageDefinitionResource::class)),
            MenuItem::make('Верификация', new VerifyingUserResource())
                ->canSee($this->canSeeResource(VerifyingUserResource::class)),
            MenuItem::make('Cтейкинг', new ItcStakingResource())
                ->canSee($this->canSeeResource(ItcStakingResource::class)),
            MenuItem::make('Новости', new NewsResource())
                ->canSee($this->canSeeResource(NewsResource::class)),
            MenuItem::make('Админы', new AdminUserResource())
                ->canSee($this->canSeeResource(AdminUserResource::class)),
            MenuItem::make('Журнал действий', new GlobalAdminActivityResource())
                ->canSee($this->canSeeResource(GlobalAdminActivityResource::class)),
            MenuItem::make('Роли', new MoonShineUserRoleResource())
                ->canSee($this->canSeeResource(MoonShineUserRoleResource::class)),
            MenuItem::make('Документация', '/itcapitalmoonshineadminpanel/docs')
                ->canSee($this->canSeeMoonShineUser()),
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

        $assets = [
            '/vendor/moonshine/css/moonshine-overrides.css',
            '/vendor/moonshine/js/multi-sort.js',
            '/vendor/moonshine/js/copy-tooltip.js',
            '/vendor/moonshine/js/moonshine-auto-click-itc-packages.js',
            '/vendor/moonshine/js/paginator-top.js',
            '/vendor/moonshine/js/show-eye-in-password-field.js',
            '/vendor/moonshine/js/filters-search-preserver.js',
            '/vendor/moonshine/js/show-overflow-popup.js',
            '/vendor/moonshine/js/paginator-trim.js',
            // flex-top-content wraps H1 + first block into header-row and breaks custom layouts. disabled.
            // '/vendor/moonshine/js/flex-top-content.js',
            '/vendor/moonshine/js/set-names-for-override-percents-fields.js',
            '/vendor/moonshine/js/set-names-for-requirements-fields.js',
            '/vendor/moonshine/js/set-names-for-common-percents-fields.js',
        ];

        if (! $this->app->runningInConsole()) {
            $assets[] = Vite::asset('resources/css/app.css');
        }

        moonShineAssets()->add($assets);
    }

    private function canSeeResource(string $resourceClass): Closure
    {
        return function () use ($resourceClass): bool {
            $user = auth(config('moonshine.auth.guard', 'moonshine'))->user();

            if (! $user instanceof MoonshineUser) {
                return false;
            }

            if ($this->hasExplicitPermissions($user)) {
                return $user->isHavePermission($resourceClass, 'viewAny');
            }

            return $user->isSuperUser();
        };
    }

    private function canSeeMoonShineUser(): Closure
    {
        return function (): bool {
            return auth(config('moonshine.auth.guard', 'moonshine'))->user() instanceof MoonshineUser;
        };
    }

    private function hasExplicitPermissions(MoonshineUser $user): bool
    {
        return $user->moonshineUserPermission !== null;
    }
}
