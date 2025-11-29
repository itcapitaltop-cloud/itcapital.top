<?php

namespace App\Contracts\Repositories;

use App\Dto\Partners\PartnerRankDataTransferObject;
use Illuminate\Support\Collection;

interface PartnerRepositoryContract
{
    /**
     * @return \Illuminate\Support\Collection<int, \App\Dto\Partners\PartnerRankDataTransferObject>
     */
    public function requirements(): Collection;

    /**
     * @param int $level
     * @return \App\Dto\Partners\PartnerRankDataTransferObject|null
     */
    public function findRankByLevel(int $level): ?PartnerRankDataTransferObject;

    /**
     * Получить бонус за ранг
     */
    public function getBonusForRank(int $rank): float;
}
