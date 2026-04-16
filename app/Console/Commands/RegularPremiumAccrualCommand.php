<?php

namespace App\Console\Commands;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerLevelPercent;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegularPremiumAccrualCommand extends Command
{
    protected $signature = 'regular-premium:accrual
                            {--user= : ID пользователя‑аплайна, для которого считаем премию}
                            {--reset : удалить начисления за последние 14 дней перед пересчётом}';

    protected $description = 'Начислить регулярную премию';

    private Carbon $from;

    private Carbon $to;

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->to = now()->startOfWeek();
        $this->from = $this->to->copy()->subWeek();

        $onlyUser = $this->option('user') ? (int) $this->option('user') : null;

        if ($onlyUser && $this->option('reset')) {
            $this->wipeUserData($onlyUser);
        }

        $descendantsNet = $this->collectNetProfitPerUser();

        $rewards = [];
        $details = [];

        $closure = PartnerClosure::whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'));

        if ($this->option('reset')) {
            $this->wipeAllData();
        }

        $closure->orderBy('depth')
            ->each(function ($row) use ($descendantsNet, &$rewards, &$details) {

                $descId = $row->descendant_id;
                $ancestor = $row->ancestor_id;
                $line = $row->depth;

                if (! isset($descendantsNet[$descId])) {
                    return;
                }
                $net = $descendantsNet[$descId];

                if ($net <= 0) {
                    return;
                }

                $percent = $this->percentForAncestor($ancestor, $line);

                if ($percent <= 0) {
                    return;
                }

                $reward = round($net * $percent / 100, 2);

                $this->line(
                    "anc {$ancestor}: {$net} × {$percent}% = {$reward} "
                    . "(desc {$descId}, L{$line})"
                );

                if ($reward <= 0) {
                    return;
                }

                $rewards[$ancestor] = ($rewards[$ancestor] ?? 0) + $reward;
                $details[$ancestor][] = [$descId, $line, $reward];
            });

        foreach ($rewards as $userId => $sum) {

            if ($onlyUser && $userId !== $onlyUser) {
                continue;
            }

            DB::transaction(static function () use ($userId, $sum, $details) {

                $trxUuid = 'RP-' . Str::random(10);

                Transaction::create([
                    'uuid' => $trxUuid,
                    'user_id' => $userId,
                    'amount' => $sum,
                    'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL,
                    'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
                    'accepted_at' => now(),
                ]);

                foreach ($details[$userId] as [$descId, $line, $amount]) {
                    PartnerReward::create([
                        'uuid' => $trxUuid,
                        'from_user_id' => $descId,
                        'reward_type' => PartnerRewardTypeEnum::REGULAR->value,
                        'line' => $line,
                        'amount' => $amount,
                        'trx_uuid' => $trxUuid,
                    ]);

                    $descendant = User::query()->find($descId);

                    if ($descendant !== null) {
                        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                            type: ActivityEventTypeEnum::PartnerRegularBonusReceived,
                            userId: $userId,
                            subject: $descendant,
                            feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                            properties: [
                                'amount' => $amount,
                                'line' => $line,
                                'from_username' => $descendant->username,
                                'transaction_uuid' => $trxUuid,
                            ],
                            causer: $descendant,
                            logName: 'partners',
                            context: 'system',
                        ));
                    }
                }
            });

            Notify::bonusRegular(User::where('id', $userId)->first(), $sum);
        }

        $this->info('Regular premium accrual completed' . ($onlyUser ? " for user {$onlyUser}" : ' for all users') . '.');

        $csvFile = storage_path('app/public/regular_premium_report_detailed.csv');
        $handle = fopen($csvFile, 'w');
        fputcsv($handle, ['User ID', 'User Name', 'Total Amount', 'Descendant ID', 'Line', 'Detail Amount']);

        foreach ($rewards as $userId => $sum) {
            $user = User::find($userId);

            foreach ($details[$userId] as [$descId, $line, $amount]) {
                fputcsv($handle, [
                    $userId,
                    $user?->name ?? 'Unknown',
                    $sum,
                    $descId,
                    $line,
                    $amount,
                ]);
            }
        }

        fclose($handle);

        return self::SUCCESS;
    }

    private function collectNetProfitPerUser(): array
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->whereNotIn('itc_packages.type', [
                PackageTypeEnum::ARCHIVE,
                PackageTypeEnum::STAKING,
            ])
            ->with([
                'profits' => fn ($q) => $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'reinvestProfitsAll' => fn ($q) => $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
            ])
            ->get();

        $net = [];

        foreach ($packages as $pkg) {

            $user_id = $pkg->user_id;

            $dividends = $pkg->profits->sum('amount');

            $reinvests = $pkg->reinvestProfitsAll->sum('amount');

            $net[$user_id] = ($net[$user_id] ?? 0) + $dividends + $reinvests;

            $this->line(
                "USER_ID {$user_id}: dividends={$dividends} + reinvests={$reinvests} = {$net[$user_id]}"
            );
        }

        return $net;
    }

    private function percentForAncestor(int $ancestorId, int $line): float
    {
        $user = User::find($ancestorId);
        $level = $user?->rank;

        if ($line >= 6 && $line <= 20) {
            if (! $user->extended_lines) {
                return 0;
            }

            return (float) PartnerLevelPercent::where([
                'partner_level_id' => $level,
                'bonus_type' => 'regular',
                'line' => $line,
            ])->value('percent');
        }

        $override = DB::table('user_level_percent_overrides')
            ->where('user_id', $ancestorId)
            ->where('partner_level_id', $level)
            ->where('bonus_type', 'regular')
            ->where('line', $line)
            ->value('percent');

        if (! is_null($override)) {
            return (float) $override;
        }

        return (float) PartnerLevelPercent::where([
            'partner_level_id' => $level,
            'bonus_type' => 'regular',
            'line' => $line,
        ])->value('percent');
    }

    /**
     * @throws \Throwable
     */
    private function wipeUserData(int $userId): void
    {
        DB::transaction(function () use ($userId) {

            $trxUuids = Transaction::where('user_id', $userId)
                ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
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

        $this->warn("Previous regular‑premium accruals for user {$userId} have been rolled back.");
    }

    /**
     * @throws \Throwable
     */
    private function wipeAllData(): void
    {
        DB::transaction(function () {

            $trxUuids = Transaction::where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
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

        $this->warn('All regular‑premium accruals for the period have been rolled back.');
    }
}
