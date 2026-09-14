<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageBodyUnlock;
use App\Services\Package\PackageBodyBalanceResolver;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Выплачивает разблокированные суммы тела пакета, у которых истёк месяц ожидания.
 *
 * Выплата пишет обычную строку `package_balance_withdraws` — ровно ту же, что и
 * мгновенный вывод из кабинета. Благодаря этому в момент выплаты сумма уходит из
 * `pending_body_unlocks` и одновременно появляется в `balance_withdraws`: разрыва,
 * при котором деньги на мгновение вернулись бы в базу начисления, не возникает.
 */
class PayoutUnlockedBodyAmountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:payout-unlocked-body-amounts
                            {--uuid= : Process a single unlock uuid, ignoring its payout date (still skips paid and cancelled unlocks)}
                            {--dry-run : Print what would be paid out without writing anything}';

    protected $description = 'Выплачивает разблокированные суммы тела пакета, у которых наступила дата выплаты';

    public function handle(
        TransactionRepositoryContract $transactionRepo,
        PackageBodyBalanceResolver $balanceResolver,
    ): int {
        $isDryRun = (bool) $this->option('dry-run');
        $singleUuid = $this->option('uuid');

        $query = PackageBodyUnlock::query();

        if ($singleUuid !== null && $singleUuid !== '') {
            $query->where('uuid', $singleUuid)->pending();
        } else {
            $query->duePayout();
        }

        $count = 0;
        $failed = 0;
        $sum = BigDecimal::zero();

        // Без orderBy: chunkById листает по курсору id и снимает сортировку только для него,
        // поэтому ведущая сортировка по payout_at ломает курсор и может пропустить строки.
        $query->chunkById(100, function ($unlocks) use (
            $transactionRepo,
            $balanceResolver,
            $isDryRun,
            &$count,
            &$failed,
            &$sum
        ): void {
            Log::debug('[PayoutUnlockedBodyAmountsCommand] batch', ['count' => $unlocks->count()]);

            foreach ($unlocks as $unlock) {
                if ($isDryRun) {
                    $this->line("[dry-run] {$unlock->uuid} — {$unlock->amount} ITC (пакет {$unlock->package_uuid})");
                    $count++;
                    $sum = $sum->plus($unlock->amount);

                    continue;
                }

                try {
                    $paid = $this->payout($unlock->uuid, $transactionRepo, $balanceResolver);

                    if ($paid === null) {
                        continue;
                    }

                    $count++;
                    $sum = $sum->plus($paid);
                } catch (Throwable $e) {
                    $failed++;

                    if ($this->isSerializationFailure($e)) {
                        Log::warning('[PayoutUnlockedBodyAmountsCommand] serialization failure, will retry on next run', [
                            'unlock_uuid' => $unlock->uuid,
                            'error' => $e->getMessage(),
                        ]);

                        continue;
                    }

                    Log::error('[PayoutUnlockedBodyAmountsCommand] payout failed', [
                        'unlock_uuid' => $unlock->uuid,
                        'error' => $e->getMessage(),
                    ]);

                    report($e);
                }
            }
        });

        $this->info("Выплачено разблокировок: {$count}, сумма: {$sum}");

        if ($failed > 0) {
            $this->warn("Не удалось выплатить: {$failed}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return BigDecimal|null the paid amount, or null when the unlock was skipped
     */
    private function payout(
        string $unlockUuid,
        TransactionRepositoryContract $transactionRepo,
        PackageBodyBalanceResolver $balanceResolver,
    ): ?BigDecimal {
        $isOutermostTransaction = DB::transactionLevel() === 0;

        return DB::transaction(function () use (
            $unlockUuid,
            $transactionRepo,
            $balanceResolver,
            $isOutermostTransaction
        ): ?BigDecimal {
            // См. PackageReinvestRepository::withdraw(): PostgreSQL принимает
            // SET TRANSACTION ISOLATION LEVEL только первым запросом транзакции,
            // а вложенный DB::transaction() открывает лишь SAVEPOINT.
            if ($isOutermostTransaction) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            }

            $unlock = PackageBodyUnlock::query()
                ->where('uuid', $unlockUuid)
                ->lockForUpdate()
                ->firstOrFail();

            if ($unlock->payout_transaction_uuid !== null) {
                Log::warning('[PayoutUnlockedBodyAmountsCommand] already paid, skipping', [
                    'unlock_uuid' => $unlock->uuid,
                    'payout_transaction_uuid' => $unlock->payout_transaction_uuid,
                ]);

                return null;
            }

            if ($unlock->cancelled_at !== null) {
                Log::warning('[PayoutUnlockedBodyAmountsCommand] cancelled by package closing, skipping', [
                    'unlock_uuid' => $unlock->uuid,
                    'cancelled_at' => $unlock->cancelled_at->toDateTimeString(),
                ]);

                return null;
            }

            $package = ItcPackage::query()
                ->where('uuid', $unlock->package_uuid)
                ->with('transaction')
                ->firstOrFail();

            $amount = BigDecimal::of($unlock->amount);
            $userId = $package->transaction->user_id;

            // Тот же тип и префикс, что у мгновенного вывода тела: история операций
            // пользователя читается одинаково независимо от способа вывода.
            $transaction = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
                balanceType: BalanceTypeEnum::MAIN,
                amount: (string) $amount,
                acceptedAt: now(),
                prefix: 'WPB-',
            ));

            PackageBalanceWithdraw::query()->create([
                'uuid' => $transaction->uuid,
                'package_uuid' => $unlock->package_uuid,
            ]);

            $unlock->payout_transaction_uuid = $transaction->uuid;
            $unlock->save();

            // Разблокировать можно любую сумму, поэтому после выплаты тело может оказаться
            // где угодно между нулём и порогом. Обнуляем доходность по тому же правилу и
            // в тот же момент, что и мгновенный вывод: деньги ушли на баланс, тело < 100.
            $remainingBody = $balanceResolver->availableToUnlock($package);

            if ($balanceResolver->isBelowMinimumRemainder($remainingBody)) {
                $package->month_profit_percent = 0;
                $package->save();

                Log::info('[PayoutUnlockedBodyAmountsCommand] body below the profitability threshold, rate zeroed', [
                    'unlock_uuid' => $unlock->uuid,
                    'package_uuid' => $package->uuid,
                    'remaining_body' => (string) $remainingBody,
                ]);
            }

            Log::info('[PayoutUnlockedBodyAmountsCommand] paid out', [
                'unlock_uuid' => $unlock->uuid,
                'package_uuid' => $unlock->package_uuid,
                'amount' => (string) $amount,
                'transaction_uuid' => $transaction->uuid,
            ]);

            return $amount;
        });
    }

    /**
     * Выплата идёт на уровне изоляции SERIALIZABLE и конкурирует с закрытием пакета
     * и мгновенным выводом. Такая гонка — не ошибка: строку выплатит следующий запуск.
     */
    private function isSerializationFailure(Throwable $e): bool
    {
        return $e instanceof QueryException && $e->getCode() === '40001';
    }
}
