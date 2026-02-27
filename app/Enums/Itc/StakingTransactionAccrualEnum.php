<?php

namespace App\Enums\Itc;

enum StakingTransactionAccrualEnum: string
{
    case Profit = 'profit';
    case TopUpBonus = 'topup_bonus';
    case PartnerBonus = 'partner_bonus';

    public function getLabel(): string
    {
        return match ($this) {
            self::Profit => 'Профит',
            self::TopUpBonus => 'Бонус x10',
            self::PartnerBonus => 'Партнёрский бонус',
        };
    }
}
