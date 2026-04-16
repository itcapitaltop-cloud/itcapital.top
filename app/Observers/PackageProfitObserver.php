<?php

declare(strict_types=1);

namespace App\Observers;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\PackageProfit;
use App\Services\ActivityLog\BusinessActivityLogger;

final class PackageProfitObserver
{
    public function created(PackageProfit $profit): void
    {
        $package = $profit->package()->with('transaction')->first();

        if ($package === null || $package->type === PackageTypeEnum::STAKING || $package->transaction === null) {
            return;
        }

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageProfitAccrued,
            userId: $package->transaction->user_id,
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $profit->amount,
                'package_uuid' => $package->uuid,
                'profit_uuid' => $profit->uuid,
            ],
            context: auth()->check() ? 'admin' : 'system',
        ));
    }
}
