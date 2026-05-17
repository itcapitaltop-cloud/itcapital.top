<?php

declare(strict_types=1);

namespace App\Actions\User\Partner;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerRankRequirement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LeMaX10\SimpleActions\Action;

final class ProgressBarAction extends Action
{
    protected function handle(?int $userId = null): array
    {
        $user = is_null($userId) ? Auth::user() : User::query()->find($userId);
        $next = max(1, $user->rank + 1);
        $manualFillLines = [];

        // Требования ТОЛЬКО для следующего ранга (R+1)
        $reqs = PartnerRankRequirement::whereHas('rank', fn ($q) => $q->where('rank', $next))->get();

        if ($user->overridden_rank) {
            $manualFillLines = PartnerRankRequirement::query()
                ->whereNotNull('line')
                ->whereHas('rank', fn ($q) => $q->where('rank', '<', $user->rank))
                ->pluck('line')
                ->unique()
                ->map(fn ($line) => (int) $line)
                ->all();
        }

        $bars = [];

        $fromDate = null;
        $personalBase = 0;
        $lineBases = [];

        if ($user->overridden_rank && $user->overridden_rank_from) {
            $fromDate = $user->overridden_rank_from;

            // стартовые базы для ранга, с которого начали считать вручную
            $baseReqs = PartnerRankRequirement::whereHas('rank',
                fn ($q) => $q->where('rank', $user->rank))->get();

            $personalBase = $baseReqs->firstWhere('line', null)?->personal_deposit ?? 0;
            $lineBases = PartnerRankRequirement::query()
                ->whereNotNull('line')
                ->whereHas('rank', fn ($q) => $q->where('rank', '<=', $user->rank))
                ->selectRaw('line, SUM(required_turnover) as total')
                ->groupBy('line')
                ->pluck('total', 'line')
                ->all();
        }

        if ($personal = $reqs->firstWhere('line', null)) {
            $baseQuery = ItcPackage::query()
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', $user->id)
                ->whereNull('itc_packages.closed_at')
                ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
                ->with('transaction:id,uuid,amount,accepted_at,user_id');

            // ВЕСЬ депозит за всё время
            $allDeposit = (clone $baseQuery)
                ->withSum('reinvestProfitsAll', 'amount')
                ->get()
                ->sum(fn ($p) => (float) $p->transaction->amount +
                    (float) $p->reinvest_profits_all_sum_amount
                );

            // Минимум для текущего (override) ранга из базы, как раньше
            $minForRank = (float) $personalBase;

            if ($user->overridden_rank && $fromDate && $allDeposit < $minForRank) {
                // "минимум + прирост с даты override"
                $since = (clone $baseQuery)
                    ->withSum([
                        'reinvestProfitsAll' => function ($q) use ($fromDate) {
                            $q->when($fromDate, fn ($qq) => $qq->where('created_at', '>=', $fromDate));
                        },
                    ], 'amount')
                    ->get()
                    ->sum(function ($p) use ($fromDate) {
                        $buy = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                            ? (float) $p->transaction->amount
                            : 0.0;

                        return $buy + (float) $p->reinvest_profits_all_sum_amount;
                    });

                $currentDeposit = $minForRank + $since;
            } else {
                $currentDeposit = $allDeposit;
            }

            $bars[] = [
                'label' => __('livewire_partners_personal_deposit_label'),
                'current' => $currentDeposit,
                'target' => $personal->personal_deposit, // НЕ кумулятивно
            ];
        }

        // Сумма требований по каждой линии от ранга 1 до R+1 (кумулятивно)
        $cumToNextByLine = PartnerRankRequirement::query()
            ->whereNotNull('line')
            ->whereHas('rank', fn ($q) => $q->where('rank', '<=', $next))
            ->selectRaw('line, SUM(required_turnover) as total')
            ->groupBy('line')
            ->pluck('total', 'line'); // [line => cum(1..R+1)]

        // Прогресс по линиям: current = сколько уже НАБРАНО в пределах R+1
        $lineReqs = $reqs->whereNotNull('line');

        foreach ($lineReqs as $r) {
            $line = $r->line;
            $target = $r->required_turnover; // требование следующего ранга (R+1)

            // При ручном ранге сохраняем излишек до override и докидываем недостающую базу.
            $actual = $this->calcEffectiveTurnoverByLine(
                userId: $user->id,
                line: $line,
                baseAmount: (float) ($lineBases[$line] ?? 0),
                fromDate: $fromDate instanceof Carbon ? $fromDate->toDateTimeString() : $fromDate,
            );

            $cumulativeToNext = (float) ($cumToNextByLine[$line] ?? 0);

            if ($user->overridden_rank) {
                $factualTurnover = $this->calcTurnoverByLine($user->id, $line);
                $displayTarget = (float) $target;
                $topUp = in_array((int) $line, $manualFillLines, true)
                    ? max(0.0, $displayTarget - $factualTurnover)
                    : 0.0;
                $current = $factualTurnover + $topUp;
            } else {
                $factualTurnover = $actual;
                $topUp = 0.0;
                $current = $factualTurnover;
                $displayTarget = $target;
            }

            $current = max(0.0, (float) $current);

            Log::debug('[ProgressBarAction.handle] line progress calculated', [
                'user_id' => $user->id,
                'line' => $line,
                'target' => (float) $target,
                'display_target' => (float) $displayTarget,
                'cumulative_to_next' => $cumulativeToNext,
                'base_amount' => (float) ($lineBases[$line] ?? 0),
                'effective_amount' => $actual,
                'factual_turnover' => $factualTurnover,
                'top_up' => $topUp,
                'current' => $current,
                'overridden_rank' => (bool) $user->overridden_rank,
            ]);

            $bars[] = [
                'label' => __('livewire_partners_line_income_label', ['line' => $line]),
                'current' => $current,
                'target' => $displayTarget,
                'factual_current' => $factualTurnover,
                'top_up' => $topUp,
            ];
        }

        return $bars;
    }

    private function calcEffectiveTurnoverByLine(int $userId, int $line, float $baseAmount, ?string $fromDate = null): float
    {
        $allAmount = $this->calcTurnoverByLine($userId, $line);

        if (! $fromDate) {
            $effectiveAmount = $baseAmount + $allAmount;

            Log::debug('[ProgressBarAction.calcEffectiveTurnoverByLine] progress without start date', [
                'user_id' => $userId,
                'line' => $line,
                'base_amount' => $baseAmount,
                'before_amount' => $allAmount,
                'since_amount' => 0.0,
                'all_amount' => $allAmount,
                'effective_amount' => $effectiveAmount,
            ]);

            return $effectiveAmount;
        }

        $sinceAmount = $this->calcTurnoverByLine($userId, $line, $fromDate);
        $beforeAmount = max(0.0, $allAmount - $sinceAmount);

        $effectiveAmount = $baseAmount + $allAmount;

        Log::debug('[ProgressBarAction.calcEffectiveTurnoverByLine] overridden rank progress', [
            'user_id' => $userId,
            'line' => $line,
            'base_amount' => $baseAmount,
            'before_amount' => $beforeAmount,
            'since_amount' => $sinceAmount,
            'all_amount' => $allAmount,
            'effective_amount' => $effectiveAmount,
        ]);

        return $effectiveAmount;
    }

    private function calcTurnoverByLine(int $userId, int $line, ?string $fromDate = null): float
    {
        $ids = PartnerClosure::query()
            ->where('ancestor_id', $userId)
            ->where('depth', $line)
            ->pluck('descendant_id');

        if ($ids->isEmpty()) {
            return 0.0;
        }

        $buyQuery = DB::table('transactions')
            ->whereIn('user_id', $ids)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE->value)
            ->whereNotNull('accepted_at');

        if ($fromDate) {
            $buyQuery->where('accepted_at', '>=', $fromDate);
        }

        $buySum = $buyQuery->sum('amount');

        $reinvestQuery = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->whereIn('transactions.user_id', $ids);

        $reinvestSum = (float) $reinvestQuery
            ->withSum([
                'reinvestProfitsAll' => function ($q) use ($fromDate) {
                    if ($fromDate) {
                        $q->where('created_at', '>=', $fromDate);
                    }
                },
            ], 'amount')
            ->get()
            ->sum('reinvest_profits_all_sum_amount');

        return $buySum + $reinvestSum;
    }
}
