<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Repositories\PartnerRepositoryContract;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\PartnerClosure;
use App\Models\User;
use App\Tasks\User\AwardUserRankBonusTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * @param \App\Contracts\Repositories\PartnerRepositoryContract $partnerRepository
     * @param \App\Contracts\Packages\ItcPackageRepositoryContract $itcPackageRepository
     * @param \App\Tasks\User\AwardUserRankBonusTask $awardUserRankBonusTask
     */
    public function __construct(
        private readonly PartnerRepositoryContract $partnerRepository,
        private readonly ItcPackageRepositoryContract $itcPackageRepository,
        private readonly AwardUserRankBonusTask $awardUserRankBonusTask,
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

        if ($newRank === $oldRank) {
            return false;
        }

        if ($withBonus && $newRank > $oldRank) {
            $this->awardUserRankBonusTask->run($user, $newRank);
            Notify::rank($user, $newRank);
        }

        $user->update(['rank' => $newRank]);

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
        $this->resetCaches();

        $rankTable = $this->partnerRepository->requirements();

        $personalDeposit = $this->calculatePersonalDeposit($user);
        $this->buildCumulativeLineRequirements($rankTable);

        foreach ($rankTable as $rankData) {
            $rank = $rankData->rank;

            if ($this->meetsRankRequirements($user, $rankData, $personalDeposit)) {
                return $rank;
            }
        }

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
            return false;
        }

        $cumLines = $this->cumLineReqByRank[$rankData->rank] ?? [];

        foreach ($cumLines as $line => $requiredTotal) {
            if ($this->getLineTurnover($user, (int) $line) < (float) $requiredTotal) {
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

        $baseAmount = $this->getBaseLineRequirement($user, $line);
        $lineIds = $this->getDescendantIds($user->id, $line);

        $buyAmount = $this->calculateBuyAmount($lineIds, $user->overridden_rank_from);
        $reinvestAmount = $this->calculateReinvestAmount($lineIds, $user->overridden_rank_from);

        return $this->lineTurnoverCache[$line] = $baseAmount + $buyAmount + $reinvestAmount;
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
        if (! $user->overridden_rank) {
            return 0.0;
        }

        $baseRank = $this->partnerRepository->findRankByLevel($user->rank);

        if (! $baseRank) {
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
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
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
    private function calculatePersonalDeposit(User $user): float
    {
        $allPersonal = $this->itcPackageRepository->personalDepositToPackage($user);

        if (! $user->overridden_rank) {
            return $allPersonal;
        }

        $baseRank = $this->partnerRepository->findRankByLevel($user->rank);

        if (! $baseRank) {
            return $allPersonal;
        }

        $personalMin = $this->getPersonalMinimumForRank($baseRank);

        if ($allPersonal >= $personalMin) {
            return $allPersonal;
        }

        $sinceSum = $this->itcPackageRepository->personalDepositSince(
            $user,
            $user->overridden_rank_from
        );

        return $personalMin + $sinceSum;
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
    }
}
