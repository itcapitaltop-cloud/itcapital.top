<?php

namespace App\Contracts\Transactions;

use App\Dto\Transactions\AfterCreateTransactionDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Models\Transaction;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;

interface TransactionRepositoryContract
{
    public function commonStore(CreateTransactionDto $dto): Transaction;

    public function getBalanceAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string;

    public function store(CreateTransactionDto $dto, Closure $callback): mixed;

    public function checkBalanceAndStore(CreateTransactionDto $dto, Closure $callback): AfterCreateTransactionDto;

    public function partnerPeriodStats(Carbon $from, ?Carbon $to = null): array;

    /**
     * Gross partner-balance accruals (debit-type transactions only, nothing subtracted)
     * since the given moment. Same metric shown on the Partner Program page's
     * "+X за неделю/месяц" — kept separate from partnerPeriodStats(), which reports
     * the net balance delta instead.
     */
    public function partnerGrossAccrualSince(Carbon $since): float;

    public function partnerLog(int $limit = 200): Collection;
}
