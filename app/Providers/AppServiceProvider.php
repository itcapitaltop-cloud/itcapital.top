<?php

namespace App\Providers;

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackagePartnerTransfer;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageProfitWithdraw;
use App\Notifications\ResetPasswordRu;
use App\Notifications\VerifyEmailRu;
use App\Observers\PackageBalanceWithdrawObserver;
use App\Observers\PackagePartnerTransferObserver;
use App\Observers\PackageProfitObserver;
use App\Observers\PackageProfitReinvestObserver;
use App\Observers\PackageProfitReinvestWithdrawObserver;
use App\Observers\PackageProfitWithdrawObserver;
use App\Repositories\GoogleDriveBackupUploaderRepository;
use App\Repositories\GoogleSheetsUploaderRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
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
        PackageProfit::observe(PackageProfitObserver::class);
        PackageProfitReinvest::observe(PackageProfitReinvestObserver::class);
        PackageProfitWithdraw::observe(PackageProfitWithdrawObserver::class);
        PackageProfitReinvestWithdraw::observe(PackageProfitReinvestWithdrawObserver::class);
        PackageBalanceWithdraw::observe(PackageBalanceWithdrawObserver::class);
        PackagePartnerTransfer::observe(PackagePartnerTransferObserver::class);

        if (! app()->environment('production')) {
            Mail::alwaysTo(config('mail.staging.address'));
        }

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
