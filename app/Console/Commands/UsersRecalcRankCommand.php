<?php

namespace App\Console\Commands;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerRank;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\UserLevelOverride;
use Illuminate\Support\Facades\Log;

class UsersRecalcRankCommand extends Command
{
    protected $signature = 'users:recalc-rank {--user= : пересчитать только указанного пользователя} {--no-bonus : не начислять бонус за повышение}';
    protected $description = 'Пересчитать фактический ранг и записать в поле users.rank';

    /** @var array<int, \App\Models\PartnerRank> Ряды рангов по убыванию */
    private array $rankTable;

    /**
     * Карта кумулятивных требований по линиям до ранга R включительно:
     * [ R => [ line => required_total_1..R ] ]
     * @var array<int, array<int, float>>
     */
    private array $cumLineReqByRank = [];

    public function handle(): int
    {
        // Таблица рангов с требованиями
        $skipBonus = (bool) $this->option('no-bonus');
        $this->rankTable = PartnerRank::with('requirements')
            ->orderByDesc('rank')
            ->get()
            ->all();

        // Построим кумулятивные требования по линиям (раз и навсегда на время команды)
        $this->buildCumLineRequirements();

//        Log::channel('source')->debug($this->cumLineReqByRank);

        $only = $this->option('user') ? (int) $this->option('user') : null;
        $query = $only ? User::whereKey($only) : User::query();

        $updated = 0;

        $query->chunk(500, function ($users) use (&$updated, $skipBonus) {
            foreach ($users as $user) {
                $newRank = $this->calcRank($user->id);

                if ($newRank !== (int) $user->rank) {

                    // Бонус за повышение
                    if (!$skipBonus && $newRank > (int) $user->rank) {
                        $bonus = PartnerRank::where('rank', $newRank)->value('bonus_usd') ?? 0;

                        if ($bonus > 0) {
                            app(TransactionRepositoryContract::class)->commonStore(
                                new CreateTransactionDto(
                                    userId:      $user->id,
                                    trxType:     TrxTypeEnum::RANK_BONUS_ACCRUAL,
                                    balanceType: BalanceTypeEnum::PARTNER,
                                    amount:      $bonus,
                                    acceptedAt:  now(),
                                    prefix:      'RB-',
                                )
                            );
                        }
                        Notify::rank($user, $newRank);
                    }

                    User::whereKey($user->id)->update(['rank' => $newRank]);
                    $updated++;
                }
            }
        });

        $this->info("Рангов пересчитано и сохранено: {$updated}");
        return self::SUCCESS;
    }

    private function buildCumLineRequirements(): void
    {
        // Сопоставим объекту ранга его значение ранга
        $byRank = [];
        foreach ($this->rankTable as $rankRow) {
            $byRank[$rankRow->rank] = $rankRow;
        }

        // Отсортируем по возрастанию для наращивания суммы
        $ascRanks = array_keys($byRank);
        sort($ascRanks);

        $acc = []; // [line => total so far]
        foreach ($ascRanks as $r) {
            $row = $byRank[$r];
            foreach ($row->requirements->whereNotNull('line') as $req) {
                $line = (int) $req->line;
                $acc[$line] = ($acc[$line] ?? 0.0) + (float) $req->required_turnover;
            }
            $this->cumLineReqByRank[$r] = $acc;
        }
    }

    private function calcRank(int $userId): int
    {
        $user = User::withoutGlobalScope('notBanned')->find($userId);

        $fromDate = null;
        $baseRank = null;
        if ($user && $user->overridden_rank) {
            $fromDate = $user->overridden_rank_from;
            $baseRank = PartnerRank::with('requirements')
                ->where('rank', $user->rank)
                ->first();
        }

        $personalMin = 0.0;
        if ($baseRank) {
            $personalMin = (float) ($baseRank->requirements->whereNull('line')->first()?->personal_deposit ?? 0);
        }

        // Базовый запрос по всем активным пакетам пользователя (без фильтра по дате покупки)
        $baseQuery = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $userId)
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->whereNull('itc_packages.closed_at')
            ->with('transaction:id,uuid,amount,accepted_at,user_id');

        // ВЕСЬ депозит за всё время: покупка + все реинвесты
        $allPersonal = (clone $baseQuery)
            ->withSum('reinvestProfitsAll', 'amount')
            ->get()
            ->sum(fn ($p) =>
                (float) $p->transaction->amount +
                (float) $p->reinvest_profits_all_sum_amount
            );

        // Если весь депозит < минимума override-ранга — считаем как "минимум + прирост с даты override"
        if ($baseRank && $fromDate && $allPersonal < $personalMin) {
            $sinceSum = (clone $baseQuery)
                ->withSum([
                    'reinvestProfitsAll' => function ($q) use ($fromDate) {
                        $q->when($fromDate, fn ($qq) => $qq->where('created_at', '>=', $fromDate));
                    }
                ], 'amount')
                ->get()
                ->sum(function ($p) use ($fromDate) {
                    $buy = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                        ? (float) $p->transaction->amount
                        : 0.0;

                    return $buy + (float) $p->reinvest_profits_all_sum_amount;
                });

            $personal = $personalMin + $sinceSum;
        } else {
            // иначе берём весь депозит как есть
            $personal = $allPersonal;
        }

        $lineTurnover = [];

        $turnover = function (int $line) use ($userId, &$lineTurnover, $fromDate, $baseRank): float {
            if (isset($lineTurnover[$line])) {
                return $lineTurnover[$line];
            }

            $base = 0.0;
            if ($baseRank) {
                $base = $this->cumLineReqByRank[$baseRank->rank][$line] ?? 0.0;
            }

            $ids = PartnerClosure::where('ancestor_id', $userId)
                ->where('depth', $line)
                ->pluck('descendant_id');

            $buyQuery = DB::table('transactions')
                ->whereIn('user_id', $ids)
                ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
                ->whereNotNull('accepted_at');

            if ($fromDate) {
                $buyQuery->where('accepted_at', '>=', $fromDate);
            }

            $buy = (float) $buyQuery->sum('amount');

            // Реинвесты
            $reinvQuery = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->whereIn('transactions.user_id', $ids);

            $reinv = (float) $reinvQuery
                ->withSum([
                    'reinvestProfitsAll' => function ($q) use ($fromDate) {
                        if ($fromDate) {
                            $q->where('created_at', '>=', $fromDate);
                        }
                    }
                ], 'amount')
                ->get()
                ->sum('reinvest_profits_all_sum_amount');

            return $lineTurnover[$line] = $base + $buy + $reinv;
        };

        // Проверяем от максимального ранга к меньшему
        foreach ($this->rankTable as $rankRow) {
            $targetRank = (int) $rankRow->rank;

            $ok = true;

            // 1) Личный депозит — требование РАНГА (НЕ кумулятивное)
            $personalReq = $rankRow->requirements->whereNull('line')->first();
            if ($personalReq) {
                $ok = $ok && ($personal >= (float) $personalReq->personal_deposit);
            }
            if (!$ok) {
                continue;
            }

            // 2) ЛИНИИ — кумулятивные требования до targetRank включительно
            $cumLines = $this->cumLineReqByRank[$targetRank] ?? [];
            foreach ($cumLines as $line => $requiredTotal) {
                if ($turnover((int) $line) < (float) $requiredTotal) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) {
//                Log::channel('source')->debug($targetRank);
                return $targetRank;
            }
        }

        return 0;
    }
}
