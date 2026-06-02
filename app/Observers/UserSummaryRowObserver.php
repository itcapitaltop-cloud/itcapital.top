<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Facades\Log;

/**
 * Ensures every user has a user_summary row. Replaces the former
 * trg_user_summary_on_user_insert PL/pgSQL trigger.
 */
final class UserSummaryRowObserver
{
    public function __construct(
        private readonly UserSummaryService $summaryService,
    ) {}

    public function created(User $user): void
    {
        Log::debug('[UserSummaryRowObserver] create summary row', [
            'user_id' => $user->id,
        ]);

        $this->summaryService->recompute($user->id);
    }
}
