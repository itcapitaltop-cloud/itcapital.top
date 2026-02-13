<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\PartnerClosure;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
use App\Settings\GeneralSetting;
use DB;
use Illuminate\Support\Str;

final class StakingStartBonusAccrualService implements StartBonusAccrualContract
{
    /**
     * Начислить стартовую премию со стейкинга (покупки токенов)
     *
     * @throws \Throwable
     */
    public function accrue(int $buyerId, float $packageAmount): void
    {
        if ($packageAmount <= 0) {
            return;
        }

        $ancestorId = PartnerClosure::where('descendant_id', $buyerId)
            ->where('depth', 1)
            ->value('ancestor_id');

        if (! $ancestorId) {
            return;
        }

        $percent = User::findOrFail($ancestorId)->setting('start_bonus_staking_percent', app(GeneralSetting::class)->start_bonus_staking_percent);

        if ($percent <= 0) {
            return;
        }

        $generalSettings = app(GeneralSetting::class)->exchange_rate_itc * 100;

        $reward = ($packageAmount / 100 * $generalSettings);

        if ($reward <= 0) {
            return;
        }

        $this->processAccrual($ancestorId, $buyerId, $reward);
    }

    /**
     * @throws \Throwable
     */
    private function processAccrual(
        int $ancestorId,
        int $fromUserId,
        float $reward
    ): void {
        DB::transaction(function () use ($ancestorId, $fromUserId, $reward) {

            $uuid = 'SSB-' . Str::random(10);
            $now = now();

            Transaction::create([
                'uuid' => $uuid,
                'user_id' => $ancestorId,
                'amount' => $reward,
                'trx_type' => TrxTypeEnum::STAKING_START_BONUS_ACCRUAL,
                'balance_type' => BalanceTypeEnum::PARTNER,
                'accepted_at' => $now,
            ]);

            PartnerReward::create([
                'uuid' => $uuid,
                'from_user_id' => $fromUserId,
                'reward_type' => PartnerRewardTypeEnum::STAKING_START->value,
                'line' => 1,
                'amount' => $reward,
                'trx_uuid' => $uuid,
            ]);

            Notify::bonusStart(User::find($ancestorId), $reward);
        });
    }
}
