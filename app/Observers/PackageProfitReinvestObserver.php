<?php

declare(strict_types=1);

namespace App\Observers;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Services\ActivityLog\BusinessActivityLogger;

final class PackageProfitReinvestObserver
{
    public function created(PackageProfitReinvest $reinvest): void
    {
        $package = ItcPackage::query()
            ->with('transaction')
            ->where('uuid', $reinvest->package_uuid)
            ->first();

        if ($package === null || $package->type === PackageTypeEnum::STAKING || $package->transaction === null) {
            return;
        }

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageReinvested,
            userId: $package->transaction->user_id,
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $reinvest->amount,
                'package_uuid' => $package->uuid,
                'reinvest_uuid' => $reinvest->uuid,
            ],
            causer: auth()->user(),
            logName: 'packages',
            context: auth()->check() ? 'account' : 'system',
        ));
    }
}
