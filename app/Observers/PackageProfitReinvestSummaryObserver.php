<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PackageProfitReinvest;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps user_summary.reinvests_sum in sync when reinvests are created/deleted.
 * Registered alongside the existing activity-logging PackageProfitReinvestObserver.
 */
final class PackageProfitReinvestSummaryObserver
{
    public function __construct(
        private readonly UserSummaryService $summaryService,
    ) {}

    public function created(PackageProfitReinvest $reinvest): void
    {
        $this->recompute($reinvest, 'created');
    }

    public function deleted(PackageProfitReinvest $reinvest): void
    {
        $this->recompute($reinvest, 'deleted');
    }

    private function recompute(PackageProfitReinvest $reinvest, string $event): void
    {
        Log::debug('[PackageProfitReinvestSummaryObserver] recompute summary', [
            'event' => $event,
            'package_uuid' => $reinvest->package_uuid,
            'reinvest_uuid' => $reinvest->uuid,
        ]);

        $this->summaryService->recomputeByPackageUuid($reinvest->package_uuid);
    }
}
