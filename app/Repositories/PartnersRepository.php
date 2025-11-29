<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PartnerRepositoryContract;
use App\Dto\Partners\PartnerRankDataTransferObject;
use App\Dto\Partners\PartnerRequirementDataTransferObject;
use App\Models\PartnerRank;
use App\Models\PartnerRankRequirement;
use Illuminate\Support\Collection;

final class PartnersRepository implements PartnerRepositoryContract
{
    /**
     * @return \Illuminate\Support\Collection<int, \App\Dto\Partners\PartnerRankDataTransferObject>
     */
    public function requirements(): Collection
    {
        $ranks = PartnerRank::query()
            ->with('requirements')
            ->orderByDesc('rank')
            ->get();

        return $ranks->map(function (PartnerRank $rank) {
            return new PartnerRankDataTransferObject(
                rank: $rank->rank,
                bonus: $rank->bonus_usd,
                requirements: $rank->requirements->map(function (PartnerRankRequirement $requirement) {
                    return new PartnerRequirementDataTransferObject(
                        line: $requirement->line,
                        requiredTurnover: $requirement->required_turnover,
                        deposit: $requirement->personal_deposit
                    );
                }),
            );
        });
    }

    /**
     * @param int $level
     * @return \App\Dto\Partners\PartnerRankDataTransferObject|null
     */
    public function findRankByLevel(int $level): ?PartnerRankDataTransferObject
    {
        $rank = PartnerRank::query()
            ->with('requirements')
            ->where('rank', $level)
            ->first();

        if (! $rank) {
            return null;
        }

        return new PartnerRankDataTransferObject(
            rank: $rank->rank,
            bonus: $rank->bonus_usd,
            requirements: $rank->requirements->map(function (PartnerRankRequirement $requirement) {
                return new PartnerRequirementDataTransferObject(
                    line: $requirement->line,
                    requiredTurnover: $requirement->required_turnover,
                    deposit: $requirement->personal_deposit
                );
            }),
        );
    }

    /**
     * @param int $rank
     * @return float
     */
    public function getBonusForRank(int $rank): float
    {
        return (float) (PartnerRank::where('rank', $rank)->value('bonus_usd') ?? 0);
    }
}
