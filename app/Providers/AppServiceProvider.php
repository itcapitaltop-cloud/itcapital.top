<?php

namespace App\Providers;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Packages\PackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Notifications\ResetPasswordRu;
use App\Notifications\VerifyEmailRu;
use App\Repositories\GoogleDriveBackupUploaderRepository;
use App\Repositories\GoogleSheetsUploaderRepository;
use App\Repositories\ItcPackageRepository;
use App\Repositories\LogRepository;
use App\Repositories\PackageReinvestRepository;
use App\Repositories\PackageRepository;
use App\Repositories\StartBonusAccrualRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TransactionRepositoryContract::class, TransactionRepository::class);
        $this->app->bind(PackageRepositoryContract::class, PackageRepository::class);
        $this->app->bind(PackageReinvestRepositoryContract::class, PackageReinvestRepository::class);

        $this->app->bind(ItcPackageRepositoryContract::class, ItcPackageRepository::class);

        $this->app->bind(LogRepositoryContract::class, LogRepository::class);

        $this->app->bind(StartBonusAccrualContract::class, StartBonusAccrualRepository::class);

        $this->app->singleton(
            GoogleSheetsUploaderContract::class,
            GoogleSheetsUploaderRepository::class
        );

        $this->app->bind(
            GoogleDriveBackupUploaderContract::class,
            GoogleDriveBackupUploaderRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! App::hasDebugModeEnabled()) {
            $this->app['request']->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        View::composer('*', function (\Illuminate\View\View $view) {
            $view->with('isAuthPage', request()->routeIs(
                'login',
                'sign-up',
                'password-reset',
                'email-restore',
                'email-verified',
                'verify-email',
            ));
        });

        View::composer('*', function (\Illuminate\View\View $view) {
            $view->with('isAccountPage', request()->is(
                'account',
                'account/*',
            ));
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {

            return (new VerifyEmailRu())->toMail($notifiable)->view(
                'emails.verify-ru',
                ['url' => $url]
            );
        });

        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new ResetPasswordRu($token))
                ->toMail($notifiable);
        });
    }
}
