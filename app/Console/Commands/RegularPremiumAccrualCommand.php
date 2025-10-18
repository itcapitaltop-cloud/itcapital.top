<?php

namespace App\Console\Commands;

use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Helpers\Notify;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use Illuminate\Support\Facades\Log;
use App\Models\{
    ItcPackage, PackageProfit, PackageProfitReinvest,
    PackageProfitWithdraw, PackageProfitReinvestWithdraw,
    PartnerClosure, PartnerLevelPercent, PartnerReward,
    Transaction, User
};
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

    public function handle(): int
    {
        $this->to   = now()->addDay()->startOfDay();
        $this->from = $this->to->copy()->subDays(14);

        $onlyUser = $this->option('user') ? intval($this->option('user')) : null;

        if ($onlyUser && $this->option('reset')) {
            $this->wipeUserData($onlyUser);
//            return self::SUCCESS;
        }

        $descendantsNet = $this->collectNetProfitPerUser();

        $rewards  = [];
        $details  = [];

        $closure = PartnerClosure::whereBetween('depth', [1, 8])
            ->whereIn('descendant_id', User::whereNull('banned_at')->pluck('id'));
        if ($this->option('reset')) {
//            $onlyUser
//                ? $this->wipeUserData($onlyUser)
                /*:*/ $this->wipeAllData();
        }

        $closure->orderBy('depth')
            ->each(function ($row) use ($descendantsNet, &$rewards, &$details) {

                $descId   = $row->descendant_id;
                $ancestor = $row->ancestor_id;
                $line     = $row->depth;

                if (!isset($descendantsNet[$descId])) return;
                $net = $descendantsNet[$descId];
                if ($net <= 0) return;

                $percent = $this->percentForAncestor($ancestor, $line);
                if ($percent <= 0) return;

                $reward = round($net * $percent / 100, 2);

                $this->line(
                    "anc {$ancestor}: {$net} × {$percent}% = {$reward} "
                    . "(desc {$descId}, L{$line})"
                );

                if ($reward <= 0) return;

                $rewards[$ancestor] = ($rewards[$ancestor] ?? 0) + $reward;
                $details[$ancestor][] = [$descId, $line, $reward];
            });

        foreach ($rewards as $userId => $sum) {

            if ($onlyUser && $userId !== $onlyUser) continue;

            DB::transaction(function () use ($userId, $sum, $details) {

                $trxUuid = 'RP-'.Str::random(10);

                Transaction::create([
                    'uuid'         => $trxUuid,
                    'user_id'      => $userId,
                    'amount'       => $sum,
                    'trx_type'     => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL,
                    'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
                    'accepted_at'  => now(),
                ]);

                foreach ($details[$userId] as [$descId, $line, $amount]) {
                    PartnerReward::create([
                        'uuid'         => $trxUuid,
                        'from_user_id' => $descId,
                        'reward_type'  => PartnerRewardTypeEnum::REGULAR->value,
                        'line'         => $line,
                        'amount'       => $amount,
                        'trx_uuid'     => $trxUuid,
                    ]);
                }
            });

            $u = User::where('id', $userId)->first();

            Notify::bonusRegular($u, $sum);
        }

        $this->info('Regular premium accrual completed'.($onlyUser ? " for user {$onlyUser}" : ' for all users').'.');
        return self::SUCCESS;
    }

    private function collectNetProfitPerUser(): array
    {
        $packages = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->select('itc_packages.uuid', 'transactions.user_id')
            ->whereNotIn('transactions.trx_type', [TrxTypeEnum::PRESENT_PACKAGE])
            ->where(function ($q) {
                $q->whereHas('profits', fn ($p) =>
                $p->whereBetween('package_profits.created_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitsAll', fn ($p) =>
                    $p->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]))
                    ->orWhereHas('withdrawProfitsTransactions', fn ($p) =>
                    $p->whereBetween('transactions.accepted_at', [$this->from, $this->to]))
                    ->orWhereHas('reinvestProfitWithdraws', fn ($p) =>
                    $p->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]));
            })
            ->with([
                'profits' => fn ($q) =>
                $q->whereBetween('package_profits.created_at', [$this->from, $this->to]),
                'reinvestProfitsAll' => fn ($q) =>
                $q->whereBetween('package_profit_reinvests.created_at', [$this->from, $this->to]),
                'withdrawProfitsTransactions' => fn ($q) =>
                $q->whereBetween('transactions.accepted_at', [$this->from, $this->to]),
                'reinvestProfitWithdraws' => fn ($q) =>
                $q->whereBetween('package_profit_reinvest_withdraws.created_at', [$this->from, $this->to]),
            ])
            ->get();

        $net = [];

        foreach ($packages as $pkg) {

            $user_id = $pkg->user_id;

            $dividends        = $pkg->profits->sum('amount');

            $dividendsWithdraw= $pkg->withdrawProfitsTransactions->sum('amount');

            $reinvests        = $pkg->reinvestProfitsAll->sum('amount');
//            if ($user_id == 149) {
//                Log::channel('source')->debug('-------------------------------');
//                Log::channel('source')->debug($user_id);
//                Log::channel('source')->debug($dividendsWithdraw);
//                Log::channel('source')->debug($dividends);
//                Log::channel('source')->debug($reinvests);
//                Log::channel('source')->debug('-----------------------------------');
//            }
            $withdrawUuids    = $pkg->reinvestProfitWithdraws->pluck('reinvest_uuid');

            $reinvestsWithdraw= $pkg->reinvestProfitsAll
                ->whereIn('uuid', $withdrawUuids)
                ->sum('amount');

            $net[$user_id] = ($net[$user_id] ?? 0)
                + ($dividends - $dividendsWithdraw)
                + ($reinvests - $reinvestsWithdraw);
        }
        $this->line(
            "USER_ID {$user_id}: ({$dividends} − {$dividendsWithdraw}) + "
            . "({$reinvests} − {$reinvestsWithdraw}) = {$net[$user_id]}"
        );
        return $net;
    }

    private function percentForAncestor(int $ancestorId, int $line): float
    {
        $user  = User::find($ancestorId);
        $level = $user?->rank;

        if ($line >= 6 && $line <= 20) {
            if (! $user->extended_lines) {
                return 0;
            }
            return (float) PartnerLevelPercent::where([
                'partner_level_id' => $level,
                'bonus_type'       => 'regular',
                'line'             => $line,
            ])->value('percent');
        }

        $override = DB::table('user_level_percent_overrides')
            ->where('user_id', $ancestorId)
            ->where('partner_level_id', $level)
            ->where('bonus_type', 'regular')
            ->where('line', $line)
            ->value('percent');

        if (!is_null($override)) return (float) $override;

        return (float) PartnerLevelPercent::where([
            'partner_level_id' => $level,
            'bonus_type'       => 'regular',
            'line'             => $line,
        ])->value('percent');
    }

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
