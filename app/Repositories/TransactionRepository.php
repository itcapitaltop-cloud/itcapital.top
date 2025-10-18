<?php

namespace App\Repositories;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\AfterCreateTransactionDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\PartnerReward;
use App\Models\Transaction;
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
        $debits = collect(TrxTypeEnum::getDebits())->map(fn ($e) => $e->value)->toArray();
        $credits = collect(TrxTypeEnum::getCredits())->map(fn ($e) => $e->value)->toArray();

        $debitsList = "'" . implode("','", $debits) . "'";
        $creditsList = "'" . implode("','", $credits) . "'";

        $sum = Transaction::query()
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->whereNull('rejected_at')
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

        return (string) ($sum ?? 0);
    }

    public function getInvestedAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string
    {
        return (string) Transaction::query()
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->where('trx_type', TrxTypeEnum::DEPOSIT)
            ->whereNotNull('accepted_at')
            ->sum('amount');
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
        $userId = Auth::id();

        return PartnerReward::query()
            ->select([
                'partner_rewards.*',
                'descendant.depth as real_depth',
                'users.username as from_username',
            ])
            ->join('transactions', 'transactions.uuid', '=', 'partner_rewards.uuid')
            ->leftJoin('partner_closures as descendant', function ($join) use ($userId) {
                $join->on('descendant.descendant_id', '=', 'partner_rewards.from_user_id')
                    ->where('descendant.ancestor_id', '=', $userId);
            })
            ->leftJoin('users', 'users.id', '=', 'partner_rewards.from_user_id')
            ->where('transactions.user_id', $userId)
            ->whereIn('partner_rewards.reward_type', [
                PartnerRewardTypeEnum::START->value,
                PartnerRewardTypeEnum::REGULAR->value,
            ])
            ->orderByDesc('partner_rewards.created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'date' => $r->created_at->format('d.m.Y, H:i'),
                'user' => $r->from_username ?? '—',
                'level' => $r->real_depth ?? $r->line,
                'event' => match ($r->reward_type) {
                    PartnerRewardTypeEnum::START->value => 'Получена стартовая премия ' . scale((float) $r->amount) . ' ITC',
                    PartnerRewardTypeEnum::REGULAR->value => 'Получена регулярная премия ' . scale((float) $r->amount) . ' ITC',
                    default => '—',
                },
            ]);
    }

    public function packageLog(int $limit = 200): Collection
    {
        $ownPackageUuids = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', Auth::id())
            ->pluck('itc_packages.uuid');

        $trxRows = Transaction::query()
            ->where('user_id', Auth::id())
            ->whereIn('trx_type', [
                TrxTypeEnum::BUY_PACKAGE,
                TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
                TrxTypeEnum::WITHDRAW_PACKAGE,
                TrxTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT,
                TrxTypeEnum::PRESENT_PACKAGE,
                TrxTypeEnum::ZERO_PRESENT_PACKAGE,
            ])
            ->select(['accepted_at', 'created_at', 'trx_type', 'amount'])
            ->orderByDesc('accepted_at')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'date' => $t->accepted_at ?? $t->created_at,
                'event' => $t->trx_type->getName() . ' ' . scale((float) $t->amount),
            ]);

        $reinvestRows = PackageProfitReinvest::query()
            ->whereIn('package_uuid', $ownPackageUuids)
            ->select(['created_at', 'amount'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'date' => $r->created_at,
                'event' => 'Реинвест дивидендов ' . scale((float) $r->amount),
            ]);

        return $trxRows
            ->merge($reinvestRows)
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->map(fn ($row) => [
                // форматируем дату только перед выдачей
                'date' => $row['date']->format('d.m.Y, H:i'),
                'event' => $row['event'],
            ]);
    }
}
