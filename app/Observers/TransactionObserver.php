<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the user_summary projection in sync with the transactions ledger.
 * Covers investments_sum, partner_balance, buy_packages_sum, first_package_at.
 */
final class TransactionObserver
{
    public function __construct(
        private readonly UserSummaryService $summaryService,
    ) {}

    public function created(Transaction $transaction): void
    {
        $this->recompute($transaction, 'created');
    }

    public function updated(Transaction $transaction): void
    {
        $this->recompute($transaction, 'updated');
    }

    public function deleted(Transaction $transaction): void
    {
        $this->recompute($transaction, 'deleted');
    }

    private function recompute(Transaction $transaction, string $event): void
    {
        Log::debug('[TransactionObserver] recompute summary', [
            'event' => $event,
            'user_id' => $transaction->user_id,
            'trx_uuid' => $transaction->uuid,
            'trx_type' => $transaction->trx_type?->value,
        ]);

        $this->summaryService->recompute($transaction->user_id);
    }
}
