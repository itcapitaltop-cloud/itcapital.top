<?php

namespace App\Repositories;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\AfterCreateTransactionDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\Transaction;
use App\Services\ActivityLog\ActivityFeedService;
use App\Services\User\UserBalanceCalculator;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionRepository implements TransactionRepositoryContract
{
    public function commonStore(CreateTransactionDto $dto): Transaction
    {
        return Transaction::query()->create([
            'uuid' => $dto->prefix . Str::random(10),
            'amount' => $dto->amount,
            'trx_type' => $dto->trxType,
            'balance_type' => $dto->balanceType,
            'user_id' => $dto->userId,
            'accepted_at' => $dto->acceptedAt,
            'rejected_at' => $dto->rejectedAt,
        ]);
    }

    public function getBalanceAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string
    {
        return app(UserBalanceCalculator::class)->balanceFor($userId, $balanceType);
    }

    public function store(CreateTransactionDto $dto, Closure $callback): mixed
    {
        return DB::transaction(function () use ($dto, $callback) {
            $transaction = $this->commonStore($dto);

            $model = $callback($transaction);

            return [
                'transaction' => $transaction,
                'model' => $model,
            ];
        });
    }

    public function partnerPeriodStats(Carbon $from, ?Carbon $to = null): array
    {
        $to ??= now();

        $before = $this->balanceUpTo($from->copy()->subSecond());
        $after = $this->balanceUpTo($to);

        return [
            'start' => $before,            // баланс на начало
            'end' => $after,             // баланс на конец
            'delta' => $after - $before,   // изменение (может быть < 0)
        ];
    }

    /** баланс партнёрского счёта на конец $moment */
    private function balanceUpTo(Carbon $moment): float
    {
        $debits = collect(TrxTypeEnum::getDebits())->map(fn ($e) => $e->value)->toArray();
        $credits = collect(TrxTypeEnum::getCredits())->map(fn ($e) => $e->value)->toArray();

        $debitsList = "'" . implode("','", $debits) . "'";
        $creditsList = "'" . implode("','", $credits) . "'";

        $sum = Transaction::query()
            ->where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereNull('rejected_at')
            ->where('accepted_at', '<=', $moment)
            ->selectRaw("
            SUM(
                CASE
                    WHEN trx_type IN ($debitsList) THEN amount
                    WHEN trx_type IN ($creditsList) THEN -amount
                    ELSE 0
                END
            ) as balance
        ")
            ->value('balance');

        return (float) ($sum ?? 0);
    }

    /**
     * @param CreateTransactionDto $dto
     * @param Closure(Transaction $transaction): AfterCreateTransactionDto $callback
     * @return AfterCreateTransactionDto
     */
    public function checkBalanceAndStore(CreateTransactionDto $dto, Closure $callback): AfterCreateTransactionDto
    {
        return DB::transaction(function () use ($dto, $callback) {
            DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $amount = $this->getBalanceAmountByUserIdAndType($dto->userId, $dto->balanceType);
            //            Log::channel('source')->debug($amount);

            if ($amount - $dto->amount < 0) {
                throw new InvalidAmountException();
            }

            $transaction = $this->commonStore($dto);
            $model = $callback($transaction);

            return new AfterCreateTransactionDto(
                transaction: $transaction,
                model: $model
            );
        });
    }

    public function partnerLog(int $limit = 200): Collection
    {
        return app(ActivityFeedService::class)->partnerFeed(Auth::id(), $limit);
    }

    public function packageLog(int $limit = 200): Collection
    {
        return app(ActivityFeedService::class)->packageFeed(Auth::id(), $limit);
    }

    public function getRegularBonus(?int $userId = null): float
    {
        $debit = Transaction::query()
            ->when(! is_null($userId), function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getDebits())
            ->sum('amount');

        $credit = Transaction::query()
            ->when(! is_null($userId), function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getCredits())
            ->sum('amount');

        return $debit - $credit;
    }
}
