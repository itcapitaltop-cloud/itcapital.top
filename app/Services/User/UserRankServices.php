<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Repositories\PartnerRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\PartnerClosure;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Tasks\User\AwardUserRankBonusTask;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UserRankServices
{
    /**
     * Кэш кумулятивных требований по линиям
     *
     * @var array<int, array<int, float>>
     */
    private array $cumLineReqByRank = [];

    /**
     * Кэш оборота по линиям для текущего пользователя
     *
     * @var array<int, float>
     */
    private array $lineTurnoverCache = [];

    private bool $useManualRankBaseline = false;

    /**
     * @param \App\Contracts\Repositories\PartnerRepositoryContract $partnerRepository
     * @param \App\Contracts\Packages\ItcPackageRepositoryContract $itcPackageRepository
     * @param \App\Tasks\User\AwardUserRankBonusTask $awardUserRankBonusTask
     */
    public function __construct(
        private readonly PartnerRepositoryContract $partnerRepository,
        private readonly ItcPackageRepositoryContract $itcPackageRepository,
        private readonly AwardUserRankBonusTask $awardUserRankBonusTask,
        private readonly BusinessActivityLogger $activityLogger,
    ) {
        //
    }

    /**
     * Пересчитать и обновить ранг пользователя.
     *
     * Реалтайм-пересчёт работает только на повышение: понижение ранга
     * выполняется исключительно ежемесячным заданием обслуживания
     * (по одному шагу). Бонус начисляется один раз за ранг —
     * только когда новый ранг превышает `max_rank_awarded`.
     *
     * После понижения (`rank_demoted_at` установлен) повышение возможно
     * только за счёт оборота, сгенерированного после понижения. Когда
     * пользователь снова достигает своего пикового ранга (`max_rank_awarded`),
     * базлайн понижения сбрасывается и оборот снова считается за всё время.
     */
    public function recalculateAndUpdateRank(User $user, bool $withBonus = true): bool
    {
        $this->resetCaches();

        $newRank = $this->calculateRank($user);
        $oldRank = $user->rank;
        $maxRankAwarded = (int) $user->max_rank_awarded;

        Log::debug('[UserRankServices.recalculateAndUpdateRank] calculated rank', [
            'user_id' => $user->id,
            'old_rank' => $oldRank,
            'new_rank' => $newRank,
            'max_rank_awarded' => $maxRankAwarded,
            'with_bonus' => $withBonus,
            'overridden_rank' => (bool) $user->overridden_rank,
        ]);

        if ($newRank <= $oldRank) {
            if ($newRank < $oldRank) {
                Log::debug('[UserRankServices.recalculateAndUpdateRank] downgrade suppressed (promote-only)', [
                    'user_id' => $user->id,
                    'old_rank' => $oldRank,
                    'new_rank' => $newRank,
                    'skipped_downgrade' => true,
                ]);
            }

            return false;
        }

        $bonusAwarded = $withBonus && $newRank > $maxRankAwarded;

        DB::transaction(function () use ($user, $oldRank, $newRank, $maxRankAwarded, $bonusAwarded): void {
            if ($bonusAwarded) {
                $this->awardUserRankBonusTask->run($user, $newRank);
                Notify::rank($user, $newRank);
            }

            $this->activityLogger->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PartnerRankIncreased,
                userId: $user->id,
                subject: $user,
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'old_rank' => $oldRank,
                    'new_rank' => $newRank,
                    'bonus_awarded' => $bonusAwarded,
                ],
                causer: $user,
                logName: 'partners',
                context: 'rank',
            ));

            $attributes = ['rank' => $newRank];

            if ($newRank > $maxRankAwarded) {
                $attributes['max_rank_awarded'] = $newRank;
            }

            if (! is_null($user->rank_demoted_at) && $newRank >= $maxRankAwarded) {
                $attributes['rank_demoted_at'] = null;
            }

            $user->update($attributes);
        });

        Log::info('[UserRankServices.recalculateAndUpdateRank] persisted rank change', [
            'user_id' => $user->id,
            'old_rank' => $oldRank,
            'new_rank' => $newRank,
            'max_rank_awarded' => max($maxRankAwarded, $newRank),
            'bonus_awarded' => $bonusAwarded,
            'with_bonus' => $withBonus,
        ]);

        return true;
    }

    /**
     * Применить ежемесячное обслуживание ранга (постепенное понижение).
     *
     * Для сохранения ранга `N` пользователь должен за расчётный месяц выполнить
     * требования по линиям ранга `N-2`. Если хотя бы одна линия не выполнена —
     * ранг понижается ровно на один шаг (`N → N-1`). Кумулятивный оборот и
     * `max_rank_awarded` при этом не изменяются, но фиксируется базлайн
     * `rank_demoted_at`: для возврата ранга учитывается только оборот,
     * сгенерированный после понижения.
     *
     * Метод идемпотентен в пределах расчётного окна: повторный запуск за уже
     * обработанное окно (`rank_demotion_period_end >= periodEnd`) понижение
     * не применяет — один проваленный месяц наказывается ровно один раз.
     *
     * @param \App\Models\User $user
     * @param \Carbon\CarbonInterface $periodStart Начало расчётного окна включительно
     * @param \Carbon\CarbonInterface $periodEnd Конец расчётного окна исключительно
     * @param bool $dryRun Только рассчитать и залогировать понижение, не сохраняя его
     * @return bool Был ли (или был бы при dry-run) понижен ранг
     */
    public function applyMonthlyMaintenance(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        bool $dryRun = false
    ): bool {
        $this->resetCaches();

        $currentRank = (int) $user->rank;

        if ((bool) $user->overridden_rank === true) {
            Log::debug('[UserRankServices.applyMonthlyMaintenance] skipped: manual override', [
                'user_id' => $user->id,
                'rank' => $currentRank,
            ]);

            return false;
        }

        // Идемпотентность: за один проваленный месяц ранг понижается не более
        // одного раза, повторный запуск за то же окно ничего не меняет.
        if (! is_null($user->rank_demotion_period_end) && $user->rank_demotion_period_end->gte($periodEnd)) {
            Log::debug('[UserRankServices.applyMonthlyMaintenance] skipped: window already processed', [
                'user_id' => $user->id,
                'rank' => $currentRank,
                'rank_demotion_period_end' => $user->rank_demotion_period_end->toDateTimeString(),
                'period_end' => $periodEnd->toDateTimeString(),
            ]);

            return false;
        }

        // Maintenance floor: для ранга N <= 2 порог N-2 <= 0, требований по линиям нет.
        if ($currentRank < 3) {
            Log::debug('[UserRankServices.applyMonthlyMaintenance] skipped: below maintenance floor', [
                'user_id' => $user->id,
                'rank' => $currentRank,
            ]);

            return false;
        }

        $thresholdRankLevel = $currentRank - 2;
        $thresholdRank = $this->partnerRepository->findRankByLevel($thresholdRankLevel);

        if (! $thresholdRank) {
            Log::warning('[UserRankServices.applyMonthlyMaintenance] threshold rank requirements missing', [
                'user_id' => $user->id,
                'rank' => $currentRank,
                'threshold_rank' => $thresholdRankLevel,
            ]);

            return false;
        }

        $lineRequirements = $thresholdRank->requirements->whereNotNull('line');

        if ($lineRequirements->isEmpty()) {
            Log::debug('[UserRankServices.applyMonthlyMaintenance] no line requirements for threshold rank', [
                'user_id' => $user->id,
                'rank' => $currentRank,
                'threshold_rank' => $thresholdRankLevel,
            ]);

            return false;
        }

        Log::debug('[UserRankServices.applyMonthlyMaintenance] evaluating maintenance window', [
            'user_id' => $user->id,
            'rank' => $currentRank,
            'threshold_rank' => $thresholdRankLevel,
            'period_start' => $periodStart->toDateTimeString(),
            'period_end' => $periodEnd->toDateTimeString(),
        ]);

        $maintenanceFailed = false;

        foreach ($lineRequirements as $requirement) {
            $line = (int) $requirement->line;
            $requiredTurnover = (float) $requirement->requiredTurnover;
            $monthlyTurnover = $this->monthlyLineTurnover($user, $line, $periodStart, $periodEnd);

            Log::debug('[UserRankServices.applyMonthlyMaintenance] line turnover check', [
                'user_id' => $user->id,
                'rank' => $currentRank,
                'threshold_rank' => $thresholdRankLevel,
                'line' => $line,
                'monthly_turnover' => $monthlyTurnover,
                'required_turnover' => $requiredTurnover,
            ]);

            if ($monthlyTurnover < $requiredTurnover) {
                $maintenanceFailed = true;

                break;
            }
        }

        if (! $maintenanceFailed) {
            Log::debug('[UserRankServices.applyMonthlyMaintenance] maintenance met, rank preserved', [
                'user_id' => $user->id,
                'rank' => $currentRank,
                'threshold_rank' => $thresholdRankLevel,
            ]);

            return false;
        }

        $newRank = max(1, $currentRank - 1);

        if ($dryRun) {
            Log::info('[UserRankServices.applyMonthlyMaintenance] rank demotion (dry-run, not persisted)', [
                'user_id' => $user->id,
                'old_rank' => $currentRank,
                'new_rank' => $newRank,
                'threshold_rank' => $thresholdRankLevel,
                'period_start' => $periodStart->toDateTimeString(),
                'period_end' => $periodEnd->toDateTimeString(),
                'dry_run' => true,
            ]);

            return true;
        }

        DB::transaction(function () use ($user, $currentRank, $newRank, $periodStart, $periodEnd): void {
            $user->update([
                'rank' => $newRank,
                'rank_demoted_at' => $periodEnd,
                'rank_demotion_period_end' => $periodEnd,
            ]);

            Notify::rankDecreased($user, $newRank);

            $this->activityLogger->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PartnerRankDecreased,
                userId: $user->id,
                subject: $user,
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'old_rank' => $currentRank,
                    'new_rank' => $newRank,
                    'period' => $periodStart->toDateString() . '..' . $periodEnd->toDateString(),
                ],
                causer: $user,
                logName: 'partners',
                context: 'rank',
            ));
        });

        Log::info('[UserRankServices.applyMonthlyMaintenance] rank demoted', [
            'user_id' => $user->id,
            'old_rank' => $currentRank,
            'new_rank' => $newRank,
            'threshold_rank' => $thresholdRankLevel,
            'period_start' => $periodStart->toDateTimeString(),
            'period_end' => $periodEnd->toDateTimeString(),
        ]);

        return true;
    }

    /**
     * Оборот по линии за расчётное окно [start, end).
     *
     * В отличие от кумулятивного оборота (используется для повышения), здесь
     * суммируется только оборот, сгенерированный внутри окна.
     */
    private function monthlyLineTurnover(
        User $user,
        int $line,
        CarbonInterface $start,
        CarbonInterface $end
    ): float {
        $lineIds = $this->getDescendantIds($user->id, $line);

        if ($lineIds->isEmpty()) {
            return 0.0;
        }

        $buyAmount = $this->calculateBuyAmount($lineIds, $start, $end);
        $reinvestAmount = $this->calculateReinvestAmount($lineIds, $start, $end);

        return $buyAmount + $reinvestAmount;
    }

    /**
     * Рассчитать ранг пользователя
     *
     * @param \App\Models\User $user
     * @return int
     */
    public function calculateRank(User $user): int
    {
        return $this->calculateRankForMode(
            user: $user,
            useManualRankBaseline: (bool) $user->overridden_rank,
        );
    }

    public function calculateNaturalRank(User $user): int
    {
        return $this->calculateRankForMode($user, false);
    }

    private function calculateRankForMode(User $user, bool $useManualRankBaseline): int
    {
        $this->resetCaches();
        $this->useManualRankBaseline = $useManualRankBaseline;

        $rankTable = $this->partnerRepository->requirements();
        $mode = $useManualRankBaseline ? 'manual_effective' : 'natural';

        Log::debug('[UserRankServices.calculateRank] start', [
            'user_id' => $user->id,
            'mode' => $mode,
            'current_rank' => $user->rank,
            'overridden_rank' => (bool) $user->overridden_rank,
            'overridden_rank_from' => $user->overridden_rank_from?->toDateTimeString(),
        ]);

        $personalDeposit = $this->calculatePersonalDeposit($user, $useManualRankBaseline);
        $this->buildCumulativeLineRequirements($rankTable);

        foreach ($rankTable as $rankData) {
            $rank = $rankData->rank;

            if ($this->meetsRankRequirements($user, $rankData, $personalDeposit)) {
                Log::debug('[UserRankServices.calculateRank] completed', [
                    'user_id' => $user->id,
                    'mode' => $mode,
                    'result_rank' => $rank,
                    'personal_deposit' => $personalDeposit,
                ]);

                return $rank;
            }
        }

        Log::debug('[UserRankServices.calculateRank] completed', [
            'user_id' => $user->id,
            'mode' => $mode,
            'result_rank' => 0,
            'personal_deposit' => $personalDeposit,
        ]);

        return 0;
    }

    /**
     * Проверка соответствия требованиям ранга
     *
     * @param \App\Models\User $user
     * @param object $rankData
     * @param float $personalDeposit
     * @return bool
     */
    private function meetsRankRequirements(
        User $user,
        object $rankData,
        float $personalDeposit
    ): bool {
        $personalRequirement = $rankData->requirements
            ->first(fn ($req) => is_null($req->line));

        if ($personalRequirement && $personalDeposit < (float) $personalRequirement->deposit) {
            Log::debug('[UserRankServices.meetsRankRequirements] personal deposit requirement failed', [
                'user_id' => $user->id,
                'target_rank' => $rankData->rank,
                'personal_deposit' => $personalDeposit,
                'required_deposit' => (float) $personalRequirement->deposit,
                'mode' => $this->useManualRankBaseline ? 'manual_effective' : 'natural',
            ]);

            return false;
        }

        $lineRequirements = $rankData->requirements->whereNotNull('line');

        foreach ($lineRequirements as $requirement) {
            $line = (int) $requirement->line;
            $requiredTotal = (float) $requirement->requiredTurnover;
            $lineTurnover = $this->getLineTurnover($user, $line);

            if ($lineTurnover < $requiredTotal) {
                Log::debug('[UserRankServices.meetsRankRequirements] line requirement failed', [
                    'user_id' => $user->id,
                    'target_rank' => $rankData->rank,
                    'line' => $line,
                    'line_turnover' => $lineTurnover,
                    'required_turnover' => $requiredTotal,
                    'mode' => $this->useManualRankBaseline ? 'manual_effective' : 'natural',
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Получить оборот по линии с кэшированием
     *
     * @param \App\Models\User $user
     * @param int $line
     * @return float
     */
    private function getLineTurnover(User $user, int $line): float
    {
        if (isset($this->lineTurnoverCache[$line])) {
            return $this->lineTurnoverCache[$line];
        }

        $lineIds = $this->getDescendantIds($user->id, $line);

        if (! $this->useManualRankBaseline) {
            // После понижения для повышения засчитывается только оборот,
            // сгенерированный после базлайна понижения.
            $demotedAt = $user->rank_demoted_at;

            $buyAmount = $this->calculateBuyAmount($lineIds, $demotedAt);
            $reinvestAmount = $this->calculateReinvestAmount($lineIds, $demotedAt);

            $turnover = $buyAmount + $reinvestAmount;

            Log::debug('[UserRankServices.getLineTurnover] factual line turnover', [
                'user_id' => $user->id,
                'line' => $line,
                'buy_amount' => $buyAmount,
                'reinvest_amount' => $reinvestAmount,
                'turnover' => $turnover,
                'rank_demoted_at' => $demotedAt?->toDateTimeString(),
                'mode' => 'natural',
            ]);

            return $this->lineTurnoverCache[$line] = $turnover;
        }

        $baseAmount = $this->getBaseLineRequirement($user, $line);
        $fromDate = $user->overridden_rank_from;
        $allAmount = $this->calculateBuyAmount($lineIds, null)
            + $this->calculateReinvestAmount($lineIds, null);

        if (! $fromDate) {
            $effectiveAmount = $baseAmount + $allAmount;

            Log::debug('[UserRankServices.getLineTurnover] manual line turnover without start date', [
                'user_id' => $user->id,
                'line' => $line,
                'manual_rank' => $user->rank,
                'base_amount' => $baseAmount,
                'all_amount' => $allAmount,
                'effective_amount' => $effectiveAmount,
            ]);

            return $this->lineTurnoverCache[$line] = $effectiveAmount;
        }

        $sinceAmount = $this->calculateBuyAmount($lineIds, $fromDate)
            + $this->calculateReinvestAmount($lineIds, $fromDate);
        $beforeAmount = max(0.0, $allAmount - $sinceAmount);

        $effectiveAmount = $baseAmount + $allAmount;

        Log::debug('[UserRankServices.getLineTurnover] manual line turnover', [
            'user_id' => $user->id,
            'line' => $line,
            'manual_rank' => $user->rank,
            'overridden_rank_from' => $fromDate->format(DATE_ATOM),
            'base_amount' => $baseAmount,
            'before_amount' => $beforeAmount,
            'since_amount' => $sinceAmount,
            'all_amount' => $allAmount,
            'effective_amount' => $effectiveAmount,
        ]);

        return $this->lineTurnoverCache[$line] = $effectiveAmount;
    }

    /**
     * Получить базовое требование по линии (для override ранга)
     *
     * @param \App\Models\User $user
     * @param int $line
     * @return float
     */
    private function getBaseLineRequirement(User $user, int $line): float
    {
        if (! $this->useManualRankBaseline) {
            return 0.0;
        }

        $baseRank = $this->partnerRepository->findRankByLevel($user->rank);

        if (! $baseRank) {
            Log::warning('[UserRankServices.getBaseLineRequirement] manual rank requirement not found', [
                'user_id' => $user->id,
                'manual_rank' => $user->rank,
                'line' => $line,
            ]);

            return 0.0;
        }

        return $this->cumLineReqByRank[$baseRank->rank][$line] ?? 0.0;
    }

    /**
     * Получить ID потомков на указанной линии
     *
     * @param int $userId
     * @param int $line
     * @return \Illuminate\Support\Collection
     */
    private function getDescendantIds(int $userId, int $line): Collection
    {
        return PartnerClosure::where('ancestor_id', $userId)
            ->where('depth', $line)
            ->pluck('descendant_id');
    }

    /**
     * Рассчитать сумму покупок в окне [fromDate, toDate).
     *
     * @param \Illuminate\Support\Collection $userIds
     * @param \DateTimeInterface|null $fromDate Нижняя граница включительно (по accepted_at)
     * @param \DateTimeInterface|null $toDate Верхняя граница исключительно (по accepted_at)
     * @return float
     */
    private function calculateBuyAmount(Collection $userIds, ?\DateTimeInterface $fromDate, ?\DateTimeInterface $toDate = null): float
    {
        $query = DB::table('transactions')
            ->whereIn('user_id', $userIds)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE->value)
            ->whereNotNull('accepted_at');

        if ($fromDate) {
            $query->where('accepted_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('accepted_at', '<', $toDate);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Рассчитать сумму реинвестов в окне [fromDate, toDate).
     *
     * @param \Illuminate\Support\Collection $userIds
     * @param \DateTimeInterface|null $fromDate Нижняя граница включительно (по created_at реинвеста)
     * @param \DateTimeInterface|null $toDate Верхняя граница исключительно (по created_at реинвеста)
     * @return float
     */
    private function calculateReinvestAmount(Collection $userIds, ?\DateTimeInterface $fromDate, ?\DateTimeInterface $toDate = null): float
    {
        return $this->itcPackageRepository->reinvestAmountForUsers($userIds, $fromDate, $toDate);
    }

    /**
     * Рассчитать личный депозит пользователя
     *
     * @param \App\Models\User $user
     * @return float
     */
    private function calculatePersonalDeposit(User $user, bool $useManualRankBaseline): float
    {
        $allPersonal = $this->itcPackageRepository->personalDepositToPackage($user);

        if (! $useManualRankBaseline) {
            Log::debug('[UserRankServices.calculatePersonalDeposit] factual personal deposit', [
                'user_id' => $user->id,
                'personal_deposit' => $allPersonal,
                'mode' => 'natural',
            ]);

            return $allPersonal;
        }

        $baseRank = $this->partnerRepository->findRankByLevel($user->rank);

        if (! $baseRank) {
            Log::warning('[UserRankServices.calculatePersonalDeposit] manual rank requirement not found', [
                'user_id' => $user->id,
                'manual_rank' => $user->rank,
            ]);

            return $allPersonal;
        }

        $personalMin = $this->getPersonalMinimumForRank($baseRank);

        if ($allPersonal >= $personalMin) {
            Log::debug('[UserRankServices.calculatePersonalDeposit] manual personal deposit uses factual amount', [
                'user_id' => $user->id,
                'manual_rank' => $user->rank,
                'personal_deposit' => $allPersonal,
                'personal_minimum' => $personalMin,
            ]);

            return $allPersonal;
        }

        $sinceSum = $this->itcPackageRepository->personalDepositSince(
            $user,
            $user->overridden_rank_from
        );

        $effectivePersonal = $personalMin + $sinceSum;

        Log::debug('[UserRankServices.calculatePersonalDeposit] manual personal deposit uses baseline', [
            'user_id' => $user->id,
            'manual_rank' => $user->rank,
            'personal_minimum' => $personalMin,
            'since_sum' => $sinceSum,
            'effective_personal_deposit' => $effectivePersonal,
        ]);

        return $effectivePersonal;
    }

    /**
     * Получить минимальный личный депозит для ранга
     */
    private function getPersonalMinimumForRank(object $rankData): float
    {
        $requirement = $rankData->requirements
            ->first(fn ($req) => is_null($req->line));

        return $requirement ? (float) $requirement->deposit : 0.0;
    }

    /**
     * Построить кумулятивные требования по линиям
     */
    private function buildCumulativeLineRequirements(Collection $rankTable): void
    {
        $sortedRanks = $rankTable->sortBy('rank');

        $accumulated = [];

        foreach ($sortedRanks as $rankData) {
            $lineRequirements = $rankData->requirements->whereNotNull('line');

            foreach ($lineRequirements as $requirement) {
                $line = (int) $requirement->line;
                $accumulated[$line] = ($accumulated[$line] ?? 0.0) + (float) $requirement->requiredTurnover;
            }

            $this->cumLineReqByRank[$rankData->rank] = $accumulated;
        }
    }

    /**
     * Сбросить кэши
     *
     * @return void
     */
    private function resetCaches(): void
    {
        $this->cumLineReqByRank = [];
        $this->lineTurnoverCache = [];
        $this->useManualRankBaseline = false;
    }
}
