<?php

namespace App\Exceptions\Domain;

use App\Enums\Transactions\TrxTypeEnum;

class InvalidAmountException extends \Exception
{
    public function __construct(string $message = 'На балансе недостаточно средств')
    {
        parent::__construct($message);
    }
}
