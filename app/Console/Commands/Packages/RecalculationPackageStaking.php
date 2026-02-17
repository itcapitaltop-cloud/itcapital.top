<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;
use App\Settings\GeneralSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecalculationPackageStaking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recalculation:package-staking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Пересчитываем пакеты стэйкинг добовляя x10';

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     */
    public function handle(): int
    {
        $result = [];
        $report = [];
        $totalProfit = 0;

        ItcPackage::query()
            ->with('transaction.user')
            ->whereType(PackageTypeEnum::STAKING)
            ->get()
            ->map(function (ItcPackage $package) use (&$result) {
                $username = $package->transaction->user->username;

                if (! empty($result[$username])) {
                    $result[$username] += (float) $package->transaction->amount;
                } else {
                    $result[$username] = (float) $package->transaction->amount;
                }
            });

        DB::transaction(static function () use ($result,  &$report, &$totalProfit) {
            collect($result)->each(function ($amount, $username) use (&$report, &$totalProfit) {

                $user = User::query()
                    ->where('username', $username)
                    ->first();

                if (! $user) {
                    return;
                }

                $package = Transaction::query()
                    ->where('user_id', $user->id)
                    ->whereHas('itcPackage', function ($query) {
                        $query->where('type', PackageTypeEnum::STAKING);
                    })
                    ->with('itcPackage')
                    ->orderBy('id')
                    ->first();

                if (! $package) {
                    return;
                }

                $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;

                $token = $amount * $exchangeRateItc;
                $profit = $token - $amount;

                PackageProfit::query()->create([
                    'uuid' => 'PP-' . Str::random(10),
                    'package_uuid' => $package->uuid,
                    'amount' => $profit,
                ]);

                $report[] = [
                    'username' => $username,
                    'total' => round($amount, 2),
                    'profit' => round($profit, 2),
                ];

                $totalProfit += $profit;
            });
        });

        $this->table(
            ['Username', 'Total staking', 'Profit начислен'],
            $report
        );

        $this->info('==============================');
        $this->info('TOTAL PROFIT: ' . number_format($totalProfit, 2, '.', ' '));

        return Command::SUCCESS;
    }
}
