<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages;

use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Models\PackageProfitReinvest;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Выплачивает разблокированные реинвесты, у которых истёк календарный месяц ожидания.
 *
 * Выплата идёт через `PackageReinvestRepository::withdraw()` — тот же путь, что и у
 * обычного вывода реинвеста. Он создаёт транзакцию `WPRP-` на основном балансе и строку
 * `package_profit_reinvest_withdraws`, после чего реинвест перестаёт попадать и в
 * `unlockedReinvestProfits`, и в очередь `duePayout()`. Пока выплата в ожидании, из базы
 * начисления реинвест убирает `unlocked_at`: два состояния не пересекаются, поэтому база
 * не проседает дважды.
 */
class PayoutUnlockedReinvestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:payout-unlocked-reinvests
                            {--uuid= : Process a single reinvest uuid, ignoring its payout date (still skips already paid reinvests)}
                            {--dry-run : Print what would be paid out without writing anything}';

    protected $description = 'Выплачивает разблокированные реинвесты, у которых наступила дата выплаты';

    public function handle(
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo,
    ): int {
        $isDryRun = (bool) $this->option('dry-run');
        $singleUuid = $this->option('uuid');

        $query = PackageProfitReinvest::query();

        if ($singleUuid !== null && $singleUuid !== '') {
            $query->where('uuid', $singleUuid)->unlocked();
        } else {
            $query->duePayout();
        }

        $count = 0;
        $failed = 0;
        $sum = BigDecimal::zero();

        // Без orderBy: chunkById листает по курсору id и снимает сортировку только для него,
        // поэтому ведущая сортировка по payout_at ломает курсор и может пропустить строки.
        $query->chunkById(100, function ($reinvests) use (
            $transactionRepo,
            $reinvestRepo,
            $isDryRun,
            &$count,
            &$failed,
            &$sum
        ): void {
            Log::debug('[PayoutUnlockedReinvestsCommand.handle] batch', ['count' => $reinvests->count()]);

            foreach ($reinvests as $reinvest) {
                if ($isDryRun) {
                    $this->line("[dry-run] {$reinvest->uuid} — {$reinvest->amount} ITC (пакет {$reinvest->package_uuid})");
                    $count++;
                    $sum = $sum->plus($reinvest->amount);

                    continue;
                }

                try {
                    $paid = $this->payout($reinvest->uuid, $transactionRepo, $reinvestRepo);

                    if ($paid === null) {
                        continue;
                    }

                    $count++;
                    $sum = $sum->plus($paid);
                } catch (Throwable $e) {
                    $failed++;

                    if ($this->isSerializationFailure($e)) {
                        Log::warning('[PayoutUnlockedReinvestsCommand.handle] serialization failure, will retry on next run', [
                            'reinvest_uuid' => $reinvest->uuid,
                            'package_uuid' => $reinvest->package_uuid,
                            'error' => $e->getMessage(),
                        ]);

                        continue;
                    }

                    Log::error('[PayoutUnlockedReinvestsCommand.handle] payout failed', [
                        'reinvest_uuid' => $reinvest->uuid,
                        'package_uuid' => $reinvest->package_uuid,
                        'error' => $e->getMessage(),
                    ]);

                    report($e);
                }
            }
        });

        $this->info("Выплачено реинвестов: {$count}, сумма: {$sum}");

        if ($failed > 0) {
            $this->warn("Не удалось выплатить: {$failed}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Перепроверяет строку перед выплатой и делегирует её тому же репозиторию,
     * что обслуживает обычный вывод реинвеста.
     *
     * Своей транзакции здесь намеренно нет: `withdraw()` открывает собственную и первым
     * же запросом выставляет SERIALIZABLE, а PostgreSQL принимает этот statement только
     * первым в транзакции. Внешняя обёртка превратила бы её во вложенный SAVEPOINT и
     * потеряла бы уровень изоляции.
     *
     * @return BigDecimal|null the paid amount, or null when the reinvest was skipped
     */
    private function payout(
        string $reinvestUuid,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo,
    ): ?BigDecimal {
        $reinvest = PackageProfitReinvest::query()
            ->with('package')
            ->where('uuid', $reinvestUuid)
            ->first();

        if ($reinvest === null) {
            Log::warning('[PayoutUnlockedReinvestsCommand.payout] reinvest is gone, skipping', [
                'reinvest_uuid' => $reinvestUuid,
            ]);

            return null;
        }

        if ($reinvest->withdraw()->exists()) {
            Log::warning('[PayoutUnlockedReinvestsCommand.payout] already paid, skipping', [
                'reinvest_uuid' => $reinvest->uuid,
                'package_uuid' => $reinvest->package_uuid,
            ]);

            return null;
        }

        if ($reinvest->package === null) {
            Log::warning('[PayoutUnlockedReinvestsCommand.payout] package is gone, skipping', [
                'reinvest_uuid' => $reinvest->uuid,
                'package_uuid' => $reinvest->package_uuid,
            ]);

            return null;
        }

        $amount = BigDecimal::of($reinvest->amount);

        // writeAdminAudit: false — выплату инициировал планировщик, а не администратор,
        // ровно как в ItcPackageRepository::closePackage().
        $reinvestRepo->withdraw($reinvest->uuid, $transactionRepo, false);

        Log::info('[PayoutUnlockedReinvestsCommand.payout] paid out', [
            'reinvest_uuid' => $reinvest->uuid,
            'package_uuid' => $reinvest->package_uuid,
            'amount' => (string) $amount,
        ]);

        return $amount;
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
