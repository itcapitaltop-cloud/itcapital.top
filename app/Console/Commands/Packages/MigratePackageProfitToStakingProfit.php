<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingProfit;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;
use App\Settings\GeneralSetting;
use App\Tasks\Package\CreateItcStakingTask;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MoonShine\Models\MoonshineUser;

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

                $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
                $token = $packages['total_amount'] * $exchangeRateItc;
                $profit = $token - $packages['total_amount'];

                $package = new CreateItcStakingTask()
                    ->setMothProfitPercent((float) $packages['max_month_percent'])
                    ->run((string) $packages['total_amount'], $user->id);

                StakingProfit::query()
                    ->create([
                        'uuid' => 'SPP-' . Str::random(10),
                        'package_uuid' => $package->uuid,
                        'amount' => $profit,
                    ]);

                activity('packages')
                    ->performedOn($package)
                    ->causedBy(MoonshineUser::findOrFail(1))
                    ->withProperties([
                        'amount' => $packages['total_amount'],
                        'package_uuid' => $package->uuid,
                        'package_type' => PackageTypeEnum::STAKING,
                    ])
                    ->log('admin_package_purchased');

                if (! empty($packages['profits'])) {
                    PackageProfit::query()
                        ->whereIn('id', $packages['profits'])
                        ->update(['package_uuid' => $package->uuid]);
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
