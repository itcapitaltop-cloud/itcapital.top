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
     * Memoized balances for the current request, keyed by
     * "userId|balanceType|asOf" (asOf = "live" for the current balance).
     *
     * A historical snapshot is immutable within a request, so reusing it removes
     * duplicate SUM queries (e.g. the dashboard computing the "as of now" partner
     * balance once per period stat). The live balance is also memoized so that the
     * several read-only consumers that render on a single page (component boot
     * methods, sidebar, balance pill, …) share one SUM query instead of repeating
     * it. The cache is invalidated whenever a Transaction is written (see forget()),
     * and TransactionRepository::checkBalanceAndStore passes $forceFresh so its
     * authoritative read inside the SERIALIZABLE transaction always hits the DB.
     *
     * @var array<string, string>
     */
    private array $snapshotCache = [];

    /**
     * Signed balance for a user on the given balance type, returned as a decimal string.
     *
     * When $asOf is provided, the balance is reconstructed as it stood at that moment:
     * a debit counts once it has been accepted (accepted_at <= $asOf) and a credit counts
     * from the moment it was created (created_at <= $asOf), matching how each side affects
     * the live balance. With $asOf = null the result is identical to the current balance.
     *
     * Pass $forceFresh to bypass the memoized live value and re-read from the database;
     * required for the authoritative balance check before storing a transaction.
     */
    public function balanceFor(int $userId, BalanceTypeEnum $balanceType, ?Carbon $asOf = null, bool $forceFresh = false): string
    {
        $cacheKey = $asOf !== null
            ? "{$userId}|{$balanceType->value}|{$asOf->toDateTimeString()}"
            : "{$userId}|{$balanceType->value}|live";

        if (! $forceFresh && isset($this->snapshotCache[$cacheKey])) {
            return $this->snapshotCache[$cacheKey];
        }

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

        $result = (string) ($sum ?? 0);

        $this->snapshotCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Drop every memoized balance for the given user/balance type.
     *
     * Called when a Transaction is written so that later reads in the same
     * request reflect the change instead of a stale memoized value.
     */
    public function forget(int $userId, BalanceTypeEnum $balanceType): void
    {
        $prefix = "{$userId}|{$balanceType->value}|";

        foreach (array_keys($this->snapshotCache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->snapshotCache[$key]);
            }
        }
    }
}
