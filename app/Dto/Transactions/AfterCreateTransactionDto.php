<?php

namespace App\Dto\Transactions;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

readonly class AfterCreateTransactionDto
{
    public function __construct(
        public Transaction $transaction,
        public ?Model $model
    )
    {
    }
}
