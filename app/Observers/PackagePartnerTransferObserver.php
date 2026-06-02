<?php

declare(strict_types=1);

namespace App\Observers;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Models\PackagePartnerTransfer;
use App\Services\ActivityLog\BusinessActivityLogger;

final class PackagePartnerTransferObserver
{
    public function created(PackagePartnerTransfer $transfer): void
    {
        $package = $transfer->package()->with('transaction')->first();
        $transaction = $transfer->transaction()->first();

        if ($package === null || $transaction === null || $package->transaction === null) {
            return;
        }

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageToppedUp,
            userId: $package->transaction->user_id,
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $transaction->amount,
                'package_uuid' => $package->uuid,
                'transaction_uuid' => $transaction->uuid,
                'source_balance' => 'partner',
            ],
            causer: auth()->user(),
            logName: 'packages',
            context: auth()->check() ? 'account' : 'system',
        ));
    }
}
