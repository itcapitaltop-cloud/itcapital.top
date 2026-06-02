<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;

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
     */
    public function balanceFor(int $userId, BalanceTypeEnum $balanceType): string
    {
        $debits = array_map(static fn (TrxTypeEnum $type): string => $type->value, TrxTypeEnum::getDebits());
        $credits = array_map(static fn (TrxTypeEnum $type): string => $type->value, TrxTypeEnum::getCredits());

        $debitPlaceholders = implode(',', array_fill(0, count($debits), '?'));
        $creditPlaceholders = implode(',', array_fill(0, count($credits), '?'));

        $sum = Transaction::query()
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN trx_type IN ({$debitPlaceholders})
                             AND accepted_at IS NOT NULL
                             AND rejected_at IS NULL
                            THEN amount
                        WHEN trx_type IN ({$creditPlaceholders})
                             AND rejected_at IS NULL
                            THEN -amount
                        ELSE 0
                    END
                ) as balance",
                [...$debits, ...$credits]
            )
            ->value('balance');

        return (string) ($sum ?? 0);
    }
}
