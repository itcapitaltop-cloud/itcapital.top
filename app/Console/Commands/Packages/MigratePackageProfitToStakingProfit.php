<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MigratePackageProfitToStakingProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:package-profit-to-staking-profit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переносим старые PackageProfit в StakingProfit с умножением на x10';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = Carbon::parse('2026-02-13');

        ItcPackage::whereType(PackageTypeEnum::STAKING)
            ->whereHas('profits', fn ($q) => $q->where('created_at', '>=', $date))
            ->with('profits')
            ->get()
            ->each(function ($package) use ($date) {
                $package->profits()
                    ->where('created_at', '>=', $date)
                    ->forceDelete();
            });

        $stakings = ItcPackage::query()
            ->with(['transaction.user', 'profits'])
            ->whereType(PackageTypeEnum::STAKING)
            ->get()
            ->groupBy(fn ($p) => $p->transaction?->user?->username)
            ->map(function ($items) {
                return [
                    'total_amount' => $items->sum(fn ($p) => (float) ($p->transaction?->amount ?? 0)),
                    'max_month_percent' => $items->max('month_profit_percent'),
                    'package_uuids' => $items->pluck('uuid')->values()->toArray(),
                    'profits' => $items->flatMap(fn ($p) => $p->profits->pluck('id'))->values()->toArray(),
                    'transaction_uuids' => $items
                        ->pluck('transaction.uuid')
                        ->filter()
                        ->values()
                        ->toArray(),
                ];
            })
            ->toArray();

        DB::transaction(function () use ($stakings) {
            collect($stakings)->each(function (array $packages, string $username) {
                $user = User::query()
                    ->where('username', $username)
                    ->first();

                if (! $user) {
                    return;
                }

                $package = CreateStakingPackageAction::make()->run($user->id, (float) $packages['total_amount'], (float) $packages['max_month_percent']);

                new StakingAccrualService()->accrueAdminTopUpBonus($package, $packages['total_amount'], $user->id);

                if (! empty($packages['profits'])) {
                    $oldProfits = PackageProfit::query()
                        ->whereIn('id', $packages['profits'])
                        ->get();

                    $stakingService = new StakingAccrualService();

                    foreach ($oldProfits as $profit) {

                        $stakingService->accrue(
                            $package,
                            StakingTransactionAccrualEnum::Profit,
                            (float) $profit->amount,
                            $user->id,
                        );
                    }

                    PackageProfit::query()
                        ->whereIn('id', $packages['profits'])
                        ->delete();
                }

                if (! empty($packages['transaction_uuids'])) {
                    Transaction::query()
                        ->whereIn('uuid', $packages['transaction_uuids'])
                        ->delete();
                }

                if (! empty($packages['package_uuids'])) {
                    ItcPackage::query()
                        ->whereIn('uuid', $packages['package_uuids'])
                        ->delete();
                }
            });
        });

        return Command::SUCCESS;
    }
}
