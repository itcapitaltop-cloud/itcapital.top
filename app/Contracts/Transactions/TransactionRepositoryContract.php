<?php

namespace App\Contracts\Transactions;

use App\Dto\Transactions\AfterCreateTransactionDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Models\Transaction;
use Closure;

interface TransactionRepositoryContract
{
    public function commonStore(CreateTransactionDto $dto): Transaction;
    public function getBalanceAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string;
    public function store(CreateTransactionDto $dto, Closure $callback): mixed;
    public function checkBalanceAndStore(CreateTransactionDto $dto, Closure $callback): AfterCreateTransactionDto;
}
