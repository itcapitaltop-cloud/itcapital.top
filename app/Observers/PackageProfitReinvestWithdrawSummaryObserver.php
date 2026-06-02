<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps user_summary.reinvests_sum in sync when reinvest withdrawals are created/deleted.
 * Resolves the package via the linked reinvest record.
 */
final class PackageProfitReinvestWithdrawSummaryObserver
{
    public function __construct(
        private readonly UserSummaryService $summaryService,
    ) {}

    public function created(PackageProfitReinvestWithdraw $withdraw): void
    {
        $this->recompute($withdraw, 'created');
    }

    public function deleted(PackageProfitReinvestWithdraw $withdraw): void
    {
        $this->recompute($withdraw, 'deleted');
    }

    private function recompute(PackageProfitReinvestWithdraw $withdraw, string $event): void
    {
        $packageUuid = PackageProfitReinvest::query()
            ->where('uuid', $withdraw->reinvest_uuid)
            ->value('package_uuid');

        if ($packageUuid === null) {
            Log::warning('[PackageProfitReinvestWithdrawSummaryObserver] reinvest not found', [
                'event' => $event,
                'reinvest_uuid' => $withdraw->reinvest_uuid,
            ]);

            return;
        }

        Log::debug('[PackageProfitReinvestWithdrawSummaryObserver] recompute summary', [
            'event' => $event,
            'reinvest_uuid' => $withdraw->reinvest_uuid,
            'package_uuid' => $packageUuid,
        ]);

        $this->summaryService->recomputeByPackageUuid($packageUuid);
    }
}
