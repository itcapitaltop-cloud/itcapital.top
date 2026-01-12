<?php

declare(strict_types=1);

namespace App\Enums\Transactions;

enum PaymentSourcesEnum: int
{
    case Crypto = 1;
    case Fiat = 2;
}
