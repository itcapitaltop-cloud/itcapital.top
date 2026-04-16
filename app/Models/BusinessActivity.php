<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

final class BusinessActivity extends Activity
{
    protected $casts = [
        'properties' => 'collection',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    public function forUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    #[Scope]
    public function forFeed(Builder $query, ActivityFeedTypeEnum|string $feed): Builder
    {
        $value = $feed instanceof ActivityFeedTypeEnum ? $feed->value : $feed;

        return $query->whereJsonContains('properties->feeds', $value);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity> $query
     * @return \Illuminate\Database\Eloquent\Builder<\Spatie\Activitylog\Models\Activity>
     */
    #[Scope]
    public function packagesStaking(Builder $query, int $userId): Builder
    {
        return $query->where(function ($query) use ($userId) {
            $query->where(function ($query) use ($userId) {
                $query->where('subject_type', ItcPackage::class)
                    ->whereIn('description', [
                        'staking_package_purchased',
                        'staking_package_topped_up',
                        'staking_profit_accrued',
                        'package_purchased',
                        'profit_accrued',
                        'top_up_package',
                        'start_bonus_package',
                        'regular_premium_package',
                    ])
                    ->whereJsonContains('properties->package_type', PackageTypeEnum::STAKING->value)
                    ->where('causer_id', $userId);
            })->orWhere(function ($query) use ($userId) {
                $query->where('subject_type', ItcPackage::class)
                    ->where('description', 'admin_package_added_manual_profit')
                    ->whereJsonContains('properties->package_type', PackageTypeEnum::STAKING->value)
                    ->whereHasMorph(
                        'subject',
                        [ItcPackage::class],
                        fn ($query) => $query->whereHas(
                            'transaction',
                            fn ($query) => $query->where('user_id', $userId)
                        )
                    );
            });
        });
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
                        'staking_package_purchased',
                        'staking_package_topped_up',
                        'staking_profit_accrued',
                        'admin_package_purchased',
                        'profit_accrued',
                        'admin_package_changed_amount',
                        'admin_package_changed_percentage',
                        'admin_package_added_manual_profit',
                        'top_up_package',
                        'start_bonus_package',
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
