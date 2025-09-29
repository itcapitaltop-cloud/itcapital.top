<?php

namespace App\Enums\Transactions;

use Brick\Money\Currency;

enum BalanceTypeEnum: string
{
    case MAIN = 'main';
    case PARTNER = 'partner';
    case REGULAR_PREMIUM = 'regular_premium';

    public function toString(): string
    {
        return match ($this) {
            self::MAIN => 'Основной',
            self::PARTNER => 'Партнерский',
            self::REGULAR_PREMIUM => 'Регулярная премия',
        };
    }
}
