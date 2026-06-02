<?php

declare(strict_types=1);

namespace App\Observers;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\PackageProfitWithdraw;
use App\Models\Transaction;
use App\Services\ActivityLog\BusinessActivityLogger;

final class PackageProfitWithdrawObserver
{
    public function created(PackageProfitWithdraw $withdraw): void
    {
        $package = $withdraw->package()->with('transaction')->first();
        $transaction = Transaction::query()->where('uuid', $withdraw->uuid)->first();

        if ($package === null || $transaction === null || $package->type === PackageTypeEnum::STAKING || $package->transaction === null) {
            return;
        }

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageProfitWithdrawn,
            userId: $package->transaction->user_id,
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $transaction->amount,
                'package_uuid' => $package->uuid,
                'transaction_uuid' => $transaction->uuid,
            ],
            causer: auth()->user(),
            logName: 'packages',
            context: auth()->check() ? 'account' : 'system',
        ));
    }
}
