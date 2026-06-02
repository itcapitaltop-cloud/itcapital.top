<?php

declare(strict_types=1);

namespace App\Observers;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\Transaction;
use App\Services\ActivityLog\BusinessActivityLogger;

final class PackageProfitReinvestWithdrawObserver
{
    public function created(PackageProfitReinvestWithdraw $withdraw): void
    {
        $reinvest = $withdraw->reinvest()->first();

        if ($reinvest === null) {
            return;
        }

        $package = $reinvest->package()->with('transaction')->first();
        $transaction = Transaction::query()->where('uuid', $withdraw->uuid)->first();

        if ($package === null || $transaction === null || $package->type === PackageTypeEnum::STAKING || $package->transaction === null) {
            return;
        }

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageReinvestWithdrawnToBalance,
            userId: $package->transaction->user_id,
            subject: $package,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $transaction->amount,
                'package_uuid' => $package->uuid,
                'transaction_uuid' => $transaction->uuid,
                'reinvest_uuid' => $reinvest->uuid,
            ],
            causer: auth()->user(),
            logName: 'packages',
            context: auth()->check() ? 'admin' : 'system',
        ));
    }
}
