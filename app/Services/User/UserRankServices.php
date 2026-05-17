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
     * Пересчитать и обновить ранг пользователя
     */
    public function recalculateAndUpdateRank(User $user, bool $withBonus = true): bool
    {
        $this->resetCaches();

        $newRank = $this->calculateRank($user);
        $oldRank = $user->rank;

        Log::debug('[UserRankServices.recalculateAndUpdateRank] calculated rank', [
            'user_id' => $user->id,
            'old_rank' => $oldRank,
            'new_rank' => $newRank,
            'with_bonus' => $withBonus,
            'overridden_rank' => (bool) $user->overridden_rank,
        ]);

        if ($newRank === $oldRank) {
            return false;
        }

        if ($withBonus && $newRank > $oldRank) {
            $this->awardUserRankBonusTask->run($user, $newRank);
            Notify::rank($user, $newRank);

            $this->activityLogger->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PartnerRankIncreased,
                userId: $user->id,
                subject: $user,
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'old_rank' => $oldRank,
                    'new_rank' => $newRank,
                    'bonus_awarded' => true,
                ],
                causer: $user,
                logName: 'partners',
                context: 'rank',
            ));
        }

        $user->update(['rank' => $newRank]);

        Log::info('[UserRankServices.recalculateAndUpdateRank] persisted rank change', [
            'user_id' => $user->id,
            'old_rank' => $oldRank,
            'new_rank' => $newRank,
            'with_bonus' => $withBonus,
        ]);

        return true;
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
            $buyAmount = $this->calculateBuyAmount($lineIds, null);
            $reinvestAmount = $this->calculateReinvestAmount($lineIds, null);

            $turnover = $buyAmount + $reinvestAmount;

            Log::debug('[UserRankServices.getLineTurnover] factual line turnover', [
                'user_id' => $user->id,
                'line' => $line,
                'buy_amount' => $buyAmount,
                'reinvest_amount' => $reinvestAmount,
                'turnover' => $turnover,
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
     * Рассчитать сумму покупок
     *
     * @param \Illuminate\Support\Collection $userIds
     * @param \DateTime|null $fromDate
     * @return float
     */
    private function calculateBuyAmount(Collection $userIds, ?\DateTime $fromDate): float
    {
        $query = DB::table('transactions')
            ->whereIn('user_id', $userIds)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE->value)
            ->whereNotNull('accepted_at');

        if ($fromDate) {
            $query->where('accepted_at', '>=', $fromDate);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Рассчитать сумму реинвестов
     *
     * @param \Illuminate\Support\Collection $userIds
     * @param \DateTime|null $fromDate
     * @return float
     */
    private function calculateReinvestAmount(Collection $userIds, ?\DateTime $fromDate): float
    {
        return $this->itcPackageRepository->reinvestAmountForUsers($userIds, $fromDate);
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
