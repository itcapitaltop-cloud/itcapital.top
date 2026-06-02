<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Single source of truth for the signed balance of a user on a given balance type.
 *
 * Sign rules (must stay identical to the user-facing live calculation):
 *   - debit type  (TrxTypeEnum::getDebits)  → +amount, but only when accepted_at IS NOT NULL AND rejected_at IS NULL
 *   - credit type (TrxTypeEnum::getCredits) → -amount, but only when rejected_at IS NULL
 *   - anything else → 0
 *
 * Both TransactionRepository::getBalanceAmountByUserIdAndType (dashboard) and
 * UserSummaryService (admin projection) MUST go through this class so the two
 * never drift. Adding a new TrxTypeEnum case only requires updating the enum's
 * getDebits()/getCredits() lists — every consumer follows automatically.
 */
class UserBalanceCalculator
{
    /**
     * Signed balance for a user on the given balance type, returned as a decimal string.
     *
     * When $asOf is provided, the balance is reconstructed as it stood at that moment:
     * a debit counts once it has been accepted (accepted_at <= $asOf) and a credit counts
     * from the moment it was created (created_at <= $asOf), matching how each side affects
     * the live balance. With $asOf = null the result is identical to the current balance.
     */
    public function balanceFor(int $userId, BalanceTypeEnum $balanceType, ?Carbon $asOf = null): string
    {
        $debits = array_map(static fn (TrxTypeEnum $type): string => $type->value, TrxTypeEnum::getDebits());
        $credits = array_map(static fn (TrxTypeEnum $type): string => $type->value, TrxTypeEnum::getCredits());

        $debitPlaceholders = implode(',', array_fill(0, count($debits), '?'));
        $creditPlaceholders = implode(',', array_fill(0, count($credits), '?'));

        $debitTimeClause = $asOf !== null ? 'AND accepted_at <= ?' : '';
        $creditTimeClause = $asOf !== null ? 'AND created_at <= ?' : '';

        $asOfString = $asOf?->toDateTimeString();

        $bindings = [
            ...$debits,
            ...($asOf !== null ? [$asOfString] : []),
            ...$credits,
            ...($asOf !== null ? [$asOfString] : []),
        ];

        $sum = Transaction::query()
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN trx_type IN ({$debitPlaceholders})
                             AND accepted_at IS NOT NULL
                             AND rejected_at IS NULL
                             {$debitTimeClause}
                            THEN amount
                        WHEN trx_type IN ({$creditPlaceholders})
                             AND rejected_at IS NULL
                             {$creditTimeClause}
                            THEN -amount
                        ELSE 0
                    END
                ) as balance",
                $bindings
            )
            ->value('balance');

        return (string) ($sum ?? 0);
    }
}
