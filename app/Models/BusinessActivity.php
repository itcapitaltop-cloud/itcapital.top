<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

final class BusinessActivity extends Activity
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity> $query
     * @return \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity>
     */
    #[Scope]
    public function packagesStaking(Builder $query, int $userId): Builder
    {
        return $query->where('subject_type', ItcPackage::class)
            ->whereIn('description', ['package_purchased', 'profit_accrued'])
            ->whereJsonContains('properties->package_type', PackageTypeEnum::STAKING->value)
            ->where('causer_id', $userId);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity> $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity>
     */
    #[Scope]
    public function packagesStakingWithAdmin(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {

            $q->where(function ($q) use ($userId) {
                $q->where('subject_type', ItcPackage::class)
                    ->whereIn('description', [
                        'admin_package_purchased',
                        'profit_accrued',
                        'admin_package_changed_amount',
                        'admin_package_changed_percentage',
                    ])
                    ->whereJsonContains(
                        'properties->package_type',
                        PackageTypeEnum::STAKING->value
                    )
                    ->whereHasMorph(
                        'subject',
                        [ItcPackage::class],
                        fn ($q) => $q->whereHas(
                            'transaction',
                            fn ($q) => $q->where('user_id', $userId)
                        )
                    );
            });

            $q->orWhere(function ($q) {
                $q->whereIn('description', [
                    'admin_package_staking_changed_percentage',
                ]);
            });
        });
    }
}
