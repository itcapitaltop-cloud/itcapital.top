<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\PartnerClosure;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;

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

        $generalSettings = round(1 / app(TokenRateResolver::class)->currentRate(), 2);

        $reward = (float) ($packageAmount / 100 * $generalSettings);

        if ($reward <= 0) {
            return;
        }

        $transaction = Transaction::query()
            ->where('user_id', $ancestorId)
            ->whereHas('itcPackage', function ($query) {
                $query->where('type', PackageTypeEnum::STAKING);
            })
            ->with(['itcPackage'])
            ->first();

        if (is_null($transaction)) {
            $package = CreateStakingPackageAction::make()->run($ancestorId, 0);

            new StakingAccrualService()
                ->accrueStartBonus(
                    $package,
                    $reward,
                    $ancestorId,
                    $buyerId
                );

            return;
        }

        new StakingAccrualService()->accrueStartBonus($transaction->itcPackage, $reward, $ancestorId, $buyerId);
    }
}
