<?php

use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingTransactionAccrual;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-types the accruals produced by the admin "Ручной профит" form when
 * "Начисление токенов" was selected. Those rows were written as TopUpBonus,
 * which every token sum excludes as a purchase shadow, so the accrual was a
 * no-op on packages that have `staking_purchases` rows.
 *
 * Only rows that an `admin_package_added_manual_profit` activity vouches for
 * are touched; the TopUpBonus rows written alongside real purchases stay put.
 */
return new class extends Migration
{
    private const MATCH_WINDOW_SECONDS = 30;

    public function up(): void
    {
        foreach ($this->manualTopUpActivities() as $activity) {
            $accrual = $this->matchAccrual($activity, StakingTransactionAccrualEnum::TopUpBonus);

            $accrual?->update(['type' => StakingTransactionAccrualEnum::ManualTokens]);
        }
    }

    public function down(): void
    {
        foreach ($this->manualTopUpActivities() as $activity) {
            $accrual = $this->matchAccrual($activity, StakingTransactionAccrualEnum::ManualTokens);

            $accrual?->update(['type' => StakingTransactionAccrualEnum::TopUpBonus]);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, BusinessActivity>
     */
    private function manualTopUpActivities(): \Illuminate\Support\Collection
    {
        return BusinessActivity::query()
            ->where('log_name', 'admin')
            ->where('description', 'admin_package_added_manual_profit')
            ->where('subject_type', ItcPackage::class)
            ->orderBy('created_at')
            ->get()
            ->filter(fn (BusinessActivity $activity): bool => $activity->getExtraProperty('accrual_type')
                === StakingTransactionAccrualEnum::TopUpBonus->value);
    }

    private function matchAccrual(
        BusinessActivity $activity,
        StakingTransactionAccrualEnum $type,
    ): ?StakingTransactionAccrual {
        $amount = round((float) $activity->getExtraProperty('amount', 0), 2);

        if ($amount <= 0 || $activity->created_at === null) {
            return null;
        }

        return StakingTransactionAccrual::query()
            ->where('itc_package_id', $activity->subject_id)
            ->where('type', $type)
            ->whereBetween('created_at', [
                $activity->created_at->copy()->subSeconds(self::MATCH_WINDOW_SECONDS),
                $activity->created_at->copy()->addSeconds(self::MATCH_WINDOW_SECONDS),
            ])
            ->orderBy('id')
            ->get()
            ->first(fn (StakingTransactionAccrual $accrual): bool => abs((float) $accrual->amount - $amount) < 0.01);
    }
};
