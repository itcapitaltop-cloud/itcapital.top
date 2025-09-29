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
use App\Models\Partner;
use App\Models\PartnerClosure;
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
            'rejected_at' => $dto->rejectedAt
        ]);
    }

    public function getBalanceAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string
    {
        $credits = Transaction::query()->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->whereIn('trx_type', TrxTypeEnum::getCredits())
            ->whereNull('rejected_at')
            ->selectRaw('(-1 * amount) as amount');

        return DB::table(DB::table('transactions')
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType)
            ->whereNotNull('accepted_at')
            ->whereIn('trx_type', TrxTypeEnum::getDebits())
            ->select('amount')
            ->unionAll($credits), 'transactions')
            ->sum('amount');
    }

    public function getInvestedAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string
    {
        $sum = Transaction::query()
            ->where('user_id', $userId)
            ->where('balance_type', $balanceType->value)
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        return (string) $sum;
    }

    public function store(CreateTransactionDto $dto, Closure $callback): mixed
    {
        return DB::transaction(function () use ($dto, $callback) {
            $transaction = $this->commonStore($dto);

            $model = $callback($transaction);

            return [
                'transaction' => $transaction,
                'model' => $model
            ];
        });
    }

    public function partnerPeriodStats(Carbon $from, Carbon $to = null): array
    {
        $to ??= now();

        $before = $this->balanceUpTo($from->copy()->subSecond());
        $after  = $this->balanceUpTo($to);

        return [
            'start' => $before,            // баланс на начало
            'end'   => $after,             // баланс на конец
            'delta' => $after - $before,   // изменение (может быть < 0)
        ];
    }

    /** баланс партнёрского счёта на конец $moment */
    private function balanceUpTo(Carbon $moment): float
    {
        $base = Transaction::query()
            ->where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereNull('rejected_at')
            ->where('accepted_at', '<=', $moment);

        $in  = (clone $base)->whereIn('trx_type', TrxTypeEnum::getDebits()) ->sum('amount');
        $out = (clone $base)->whereIn('trx_type', TrxTypeEnum::getCredits())->sum('amount');

        return $in - $out;
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
                throw new InvalidAmountException;
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

        $rewards = PartnerReward::query()
            ->whereHas('transaction', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereIn('reward_type', [
                PartnerRewardTypeEnum::START->value,
                PartnerRewardTypeEnum::REGULAR->value,
            ])
            ->with([
                'from:id,username',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();


        // Вытаскиваем реальную глубину (линию) через PartnerClosure
        $closure = PartnerClosure::query()
            ->where('ancestor_id', $userId)
            ->whereIn('descendant_id', $rewards->pluck('from_user_id')->all())
            ->get()
            ->keyBy('descendant_id');

//        Log::channel('source')->debug($closure);

        return $rewards->map(function (PartnerReward $r) use ($closure) {
            $line = $closure[$r->from_user_id]->depth ?? $r->line;

            return [
                'date'  => $r->created_at?->format('d.m.Y, H:i') ?? '',
                'user'  => $r->from?->username ?? '—',
                'level' => $line,
                'event' => match ($r->reward_type->value) {
                    PartnerRewardTypeEnum::START->value   => 'Получена стартовая премия ' . scale((float)$r->amount) . ' ITC',
                    PartnerRewardTypeEnum::REGULAR->value => 'Получена регулярная премия ' . scale((float)$r->amount) . ' ITC',
                    default => '—',
                }
            ];
        });
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
            ->orderByDesc('accepted_at')
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $t) => [
                'date'  => $t->accepted_at ?? $t->created_at,
                'event' => $t->trx_type->getName()
                    . (scale((float)$t->amount) ? ' ' . scale((float)$t->amount) : ''),
            ]);

        $reinvestRows = PackageProfitReinvest::query()
            ->whereIn('package_uuid', $ownPackageUuids)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (PackageProfitReinvest $r) => [
                'date'  => $r->created_at,
                'event' => 'Реинвест дивидендов ' . scale((float)$r->amount),
            ]);

        return $trxRows
            ->merge($reinvestRows)
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->map(fn ($row) => [
                // форматируем дату только перед выдачей
                'date'  => $row['date']->format('d.m.Y, H:i'),
                'event' => $row['event'],
            ]);
    }
}
