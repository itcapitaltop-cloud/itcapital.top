<?php

namespace App\Enums\Itc;

enum StakingTransactionAccrualEnum: string
{
    case Profit = 'profit';
    case TopUpBonus = 'topup_bonus';
    case PartnerBonus = 'partner_bonus';
    case StartBonus = 'start_bonus';
}
