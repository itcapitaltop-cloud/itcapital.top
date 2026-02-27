<?php

namespace App\Enums\Profits;

enum StakingProfitEnum: string
{
    case TopUpBonus = 'topup_bonus';
    case RegularBonus = 'regular_bonus';
    case Profit = 'profit';
    case StartBonus = 'start_bonus';
}
