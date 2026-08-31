<?php

declare(strict_types=1);

namespace App\Services\User;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Reconciles the balance a user *sees* with the balance they actually *have*.
 *
 * `transactions.amount` is `decimal(16, 8)`, so a summed balance routinely carries
 * sub-cent digits (e.g. `1004.23617400`). Every balance is rendered at 2 decimals
 * with half-up rounding, which can display up to 0.005 MORE than exists. A user who
 * types the figure on screen then trips the "insufficient funds" guard even though
 * they asked to spend exactly what they were shown.
 *
 * This resolver keeps the display untouched and instead:
 *   - accepts a requested amount that does not exceed the DISPLAYED balance, and
 *   - clamps the amount actually debited down to the real balance.
 *
 * The user never spends money they do not have, and "spend everything" always works.
 */
final class SpendableBalanceResolver
{
    /**
     * Decimal places every user-facing balance is rendered at.
     */
    public const DISPLAY_SCALE = 2;

    /**
     * The balance as the user sees it — must stay identical to the
     * `number_format($balance, 2)` / `scale($balance)` calls in the Blade views.
     */
    public function toDisplayScale(string $balance): string
    {
        return (string) BigDecimal::of($balance)->toScale(self::DISPLAY_SCALE, RoundingMode::HALF_UP);
    }

    /**
     * Whether the requested amount is covered by the balance the user was shown.
     *
     * Deliberately compares against the DISPLAYED balance, not the raw one: the
     * sub-cent remainder is invisible to the user and must not block the request.
     */
    public function coversRequestedAmount(string $balance, string $requestedAmount): bool
    {
        return BigDecimal::of($requestedAmount)
            ->isLessThanOrEqualTo($this->toDisplayScale($balance));
    }

    /**
     * The amount that may actually be debited: the request, capped at the real balance.
     *
     * Returns a full-precision decimal string so the debit and the resulting package
     * body stay in sync and the balance lands on exactly zero when spending everything.
     */
    public function clampToBalance(string $balance, string $requestedAmount): string
    {
        $requested = BigDecimal::of($requestedAmount);
        $available = BigDecimal::of($balance);

        if ($available->isNegative()) {
            return '0';
        }

        return (string) ($requested->isGreaterThan($available) ? $available : $requested);
    }

    /**
     * Whether clamping actually reduced the request — i.e. the user hit the
     * sub-cent display gap. Useful for logging the fix in action.
     */
    public function wasClamped(string $balance, string $requestedAmount): bool
    {
        return BigDecimal::of($requestedAmount)->isGreaterThan(BigDecimal::of($balance));
    }
}
