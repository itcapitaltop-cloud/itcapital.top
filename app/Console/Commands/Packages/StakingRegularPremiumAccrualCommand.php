<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
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
    protected $signature = 'staking-regular-premium:accrual
                            {--user= : ID пользователя-аплайна}
                            {--reset : удалить начисления за последние 14 дней}';

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

        $onlyUser = $this->option('user') ? (int) $this->option('user') : null;

        if ($onlyUser && $this->option('reset')) {
            $this->wipeUserData($onlyUser);
        }

        $profits = $this->collectNetProfitPerUserFromStaking();

        if ($this->option('reset')) {
            $this->wipeAllData();
        }

        foreach ($profits as $descendantId => $profit) {

            if ($profit <= 0) {
                continue;
            }

            $ancestors = PartnerClosure::where('depth', 1)
                ->pluck('ancestor_id', 'descendant_id');

            $ancestorId = $ancestors[$descendantId] ?? null;

            if (! $ancestorId) {
                continue;
            }

            if ($onlyUser && $ancestorId !== $onlyUser) {
                continue;
            }

            $percent = User::findOrFail($ancestorId)->setting('regular_staking_percent', app(GeneralSetting::class)->regular_staking_percent);

            if ($percent <= 0) {
                continue;
            }

            $reward = round($profit * $percent / 100, 2);

            if ($reward <= 0) {
                continue;
            }

            \DB::transaction(function () use ($ancestorId, $descendantId, $reward) {

                $trxUuid = 'SRP-' . \Str::random(10);

                Transaction::create([
                    'uuid' => $trxUuid,
                    'user_id' => $ancestorId,
                    'amount' => $reward,
                    'trx_type' => TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL,
                    'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
                    'accepted_at' => now(),
                ]);

                PartnerReward::create([
                    'uuid' => $trxUuid,
                    'from_user_id' => $descendantId,
                    'reward_type' => PartnerRewardTypeEnum::STAKING_REGULAR,
                    'line' => 1,
                    'amount' => $reward,
                    'trx_uuid' => $trxUuid,
                ]);
            });

            Notify::bonusRegular(User::find($ancestorId), (string) $reward);
        }

        $this->info('Staking regular premium accrual completed.');

        return self::SUCCESS;
    }

    /**
     * @throws \Throwable
     */
    private function wipeUserData(int $userId): void
    {
        \DB::transaction(function () use ($userId) {

            $trxUuids = Transaction::where('user_id', $userId)
                ->where('trx_type', TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->pluck('uuid');

            if ($trxUuids->isEmpty()) {
                return;
            }

            PartnerReward::whereIn('uuid', $trxUuids)
                ->where('reward_type', PartnerRewardTypeEnum::REGULAR->value)
                ->delete();

            Transaction::whereIn('uuid', $trxUuids)->delete();
        });
    }

    /**
     * @throws \Throwable
     */
    private function wipeAllData(): void
    {
        \DB::transaction(function () {

            $trxUuids = Transaction::where('trx_type', TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL)
                ->whereBetween('accepted_at', [$this->from, $this->to])
                ->pluck('uuid');

            if ($trxUuids->isEmpty()) {
                return;
            }

            PartnerReward::whereIn('uuid', $trxUuids)
                ->where('reward_type', PartnerRewardTypeEnum::REGULAR->value)
                ->delete();

            Transaction::whereIn('uuid', $trxUuids)->delete();
        });
    }

    private function collectNetProfitPerUserFromStaking(): array
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereIn('itc_packages.type', [
                PackageTypeEnum::STAKING,
            ])
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
            ])
            ->get();

        $net = [];

        foreach ($packages as $pkg) {
            $regularPremium = Transaction::where('user_id', $pkg->user_id)
                ->whereIn('trx_type', [TrxTypeEnum::STAKING_START_BONUS_ACCRUAL, TrxTypeEnum::STAKING_REGULAR_PREMIUM_ACCRUAL])
                ->whereBetween('created_at', [$this->from, $this->to])
                ->sum('amount');

            $userId = $pkg->user_id;
            $profit = $pkg->profits->sum('amount');

            $net[$userId] = ($net[$userId]['amount'] ?? 0) + $profit + $regularPremium;

            $this->line("USER_ID {$userId}: staking_profit={$profit}");
        }

        return $net;
    }
}
