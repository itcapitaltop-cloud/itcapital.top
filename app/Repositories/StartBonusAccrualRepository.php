<?php

namespace App\Repositories;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Helpers\Notify;
use App\Enums\Transactions\{BalanceTypeEnum, TrxTypeEnum};
use App\Models\{
    PartnerClosure, PartnerLevelPercent, PartnerReward,
    Transaction, User
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StartBonusAccrualRepository implements StartBonusAccrualContract
{
    public function accrue(int $buyerId, float $packageAmount): void
    {
        PartnerClosure::where('descendant_id', $buyerId)
            ->whereBetween('depth', [1, 20])
            ->orderBy('depth')
            ->chunk(100, function ($rows) use ($buyerId, $packageAmount) {

                foreach ($rows as $row) {
                    $ancestor = $row->ancestor_id;
                    $line     = $row->depth;

                    $percent = $this->percentFor($ancestor, $line);
                    if ($percent <= 0) continue;

                    $reward = round($packageAmount * $percent / 100, 2);
                    if ($reward <= 0) continue;

                    DB::transaction(function () use ($ancestor, $buyerId, $line, $reward) {

                        $uuid = 'SB-'.Str::random(10);

                        Transaction::create([
                            'uuid'         => $uuid,
                            'user_id'      => $ancestor,
                            'amount'       => $reward,
                            'trx_type'     => TrxTypeEnum::START_BONUS_ACCRUAL,
                            'balance_type' => BalanceTypeEnum::PARTNER,
                            'accepted_at'  => now(),
                        ]);

                        PartnerReward::create([
                            'uuid'         => $uuid,
                            'from_user_id' => $buyerId,
                            'reward_type'  => PartnerRewardTypeEnum::START->value,
                            'line'         => $line,
                            'amount'       => $reward,
                        ]);
                    });

                    $u = User::where('id', $ancestor)->first();

                    Notify::bonusStart($u, $reward);
                }
            });
    }

    private function percentFor(int $ancestorId, int $line): float
    {
        $user  = User::find($ancestorId);
        $level = $user?->rank;

        if ($line >= 6 && $line <= 20) {
            if (! $user->extended_lines) {
                return 0;
            }
            return (float) PartnerLevelPercent::where([
                'partner_level_id' => $level,
                'bonus_type'       => 'start',
                'line'             => $line,
            ])->value('percent');
        }

        $override = DB::table('user_level_percent_overrides')
            ->where('user_id', $ancestorId)
            ->where('partner_level_id', $level)
            ->where('bonus_type', 'start')
            ->where('line', $line)
            ->value('percent');

        if (!is_null($override)) return (float) $override;

        return (float) PartnerLevelPercent::where([
            'partner_level_id' => $level,
            'bonus_type'       => 'start',
            'line'             => $line,
        ])->value('percent');
    }
}
