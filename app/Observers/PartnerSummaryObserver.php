<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Partner;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps user_summary.partners_count in sync. The upline user is Partner::partner_id.
 */
final class PartnerSummaryObserver
{
    public function __construct(
        private readonly UserSummaryService $summaryService,
    ) {}

    public function created(Partner $partner): void
    {
        $this->recompute($partner, 'created');
    }

    public function deleted(Partner $partner): void
    {
        $this->recompute($partner, 'deleted');
    }

    private function recompute(Partner $partner, string $event): void
    {
        Log::debug('[PartnerSummaryObserver] recompute summary', [
            'event' => $event,
            'partner_id' => $partner->partner_id,
        ]);

        $this->summaryService->recompute((int) $partner->partner_id);
    }
}
