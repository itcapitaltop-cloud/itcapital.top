<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use App\Settings\GeneralSetting;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

final class StakingRegularPremiumAccrualCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staking-regular-premium:accrual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Начислить регулярную премию со стейкинга';

    private CarbonInterface $from;

    private CarbonInterface $to;

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     */
    public function handle(): int
    {
        $prevMonth = now();

        $this->from = $prevMonth->copy()->startOfMonth();
        $this->to = $prevMonth->copy()->endOfMonth();

        $profits = $this->collectNetProfitPerUserFromStaking();

        foreach ($profits as $descendantId => $profit) {

            if ($profit <= 0) {
                continue;
            }

            $ancestors = PartnerClosure::where('depth', 1)
                ->pluck('ancestor_id', 'descendant_id');

            $ancestorId = $ancestors[$descendantId] ?? null;

            $package = ItcPackage::query()
                ->where('type', PackageTypeEnum::STAKING)
                ->whereHas('transaction', fn ($q) => $q->where('user_id', $ancestorId))
                ->first();

            if (! $ancestorId) {
                continue;
            }

            $percent = User::findOrFail($ancestorId)->setting('regular_staking_percent', app(GeneralSetting::class)->regular_staking_percent);

            if ($percent <= 0) {
                continue;
            }

            $reward = round($profit * $percent / 100, 2);

            if (is_null($package)) {

                $packageStaking = CreateStakingPackageAction::make()->run($ancestorId, 0);

                new StakingAccrualService()->accruePartnerBonus($packageStaking, $reward, $descendantId, $ancestorId, 1);

                continue;
            }

            if ($reward <= 0) {
                continue;
            }

            new StakingAccrualService()->accruePartnerBonus($package, $reward, $descendantId, $ancestorId, 1);

            Notify::bonusRegular(User::find($ancestorId), (string) $reward);

            $this->line("USER_ID {$descendantId}: staking_profit={$profit}");
        }

        $this->info('Staking regular premium accrual completed.');

        return self::SUCCESS;
    }

    private function collectNetProfitPerUserFromStaking(): array
    {
        return ItcPackage::query()
            ->selectRaw('transactions.user_id as user_id, SUM(sta.amount) / 100 as amount')
            ->join('transactions', 'transactions.uuid', '=', 'itc_packages.uuid')
            ->join('staking_transaction_accruals as sta', 'sta.itc_package_id', '=', 'itc_packages.id')
            ->where('itc_packages.type', PackageTypeEnum::STAKING)
            ->whereNotIn('sta.type', [StakingTransactionAccrualEnum::TopUpBonus])
            ->whereBetween('sta.created_at', [$this->from, $this->to])
            ->groupBy('transactions.user_id')
            ->pluck('amount', 'user_id')
            ->toArray();
    }
}
