<?php

declare(strict_types=1);

namespace App\ActivityLog\Strategy\Admin;

use App\Contracts\ActivityStrategyContract;
use Spatie\Activitylog\Contracts\Activity;

final class AdminPackageChangedAmount implements ActivityStrategyContract
{
    public function handle(Activity $activity): string
    {
        return __('activity/admin.admin_package_changed_amount', [
            'uuid' => $activity->getExtraProperty('package_uuid', ''),
            'amount' => $this->formatDecimal($activity->getExtraProperty('amount', 0)),
            'new_amount' => $this->formatDecimal($activity->getExtraProperty('old_amount', 0)),
        ]);
    }

    private function formatDecimal(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
