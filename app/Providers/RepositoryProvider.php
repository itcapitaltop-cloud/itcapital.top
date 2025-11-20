<?php

namespace App\Providers;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Packages\PackageRepositoryContract;
use App\Contracts\Repositories\PartnerRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Repositories\ItcPackageRepository;
use App\Repositories\LogRepository;
use App\Repositories\PackageReinvestRepository;
use App\Repositories\PackageRepository;
use App\Repositories\PartnersRepository;
use App\Repositories\StartBonusAccrualRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TransactionRepositoryContract::class, TransactionRepository::class);
        $this->app->bind(PackageRepositoryContract::class, PackageRepository::class);
        $this->app->bind(PackageReinvestRepositoryContract::class, PackageReinvestRepository::class);
        $this->app->bind(ItcPackageRepositoryContract::class, ItcPackageRepository::class);
        $this->app->bind(LogRepositoryContract::class, LogRepository::class);
        $this->app->bind(StartBonusAccrualContract::class, StartBonusAccrualRepository::class);
        $this->app->bind(PartnerRepositoryContract::class, PartnersRepository::class);
    }
}
