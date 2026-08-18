<?php

namespace App\Enums\Itc;

enum StakingTransactionAccrualEnum: string
{
    case Profit = 'profit';

    /**
     * Bookkeeping shadow of a purchase: the USD -> token conversion delta.
     * Excluded from every token/profit sum, otherwise purchases double-count.
     * Load-bearing only for packages without `staking_purchases` rows.
     */
    case TopUpBonus = 'topup_bonus';

    case PartnerBonus = 'partner_bonus';
    case StartBonus = 'start_bonus';

    /** Tokens granted by an admin on top of a package, unrelated to a purchase. */
    case ManualTokens = 'manual_tokens';
}
