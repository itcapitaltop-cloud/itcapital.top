<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackagePurchasedStrategy implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/admin.admin_package_purchased', [
            'uuid' => $activity->getExtraProperty('package_uuid', ''),
            'amount' => $this->formatDecimal($activity->getExtraProperty('amount', 0)),
            'exchange_rate' => $this->formatRate($activity->getExtraProperty('exchange_rate', 0.1)),
        ]);
    }

    private function formatDecimal(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function formatRate(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }
}
