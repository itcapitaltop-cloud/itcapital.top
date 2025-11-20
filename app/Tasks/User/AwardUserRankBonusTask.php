<?php

declare(strict_types=1);

namespace App\Tasks\User;

use App\Contracts\Repositories\PartnerRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\User;

final readonly class AwardUserRankBonusTask
{
    public function __construct(
        private PartnerRepositoryContract $partnerRepository,
        private TransactionRepositoryContract $transactionRepository,
    ) {
        // ..
    }

    /**
     * @param \App\Models\User $user
     * @param int $newRank
     * @return void
     */
    public function run(User $user, int $newRank): void
    {
        $bonus = $this->partnerRepository->getBonusForRank($newRank);

        if ($bonus <= 0) {
            return;
        }

        $this->transactionRepository->commonStore(
            new CreateTransactionDto(
                userId: $user->id,
                trxType: TrxTypeEnum::RANK_BONUS_ACCRUAL,
                balanceType: BalanceTypeEnum::PARTNER,
                amount: (string) $bonus,
                acceptedAt: now(),
                prefix: 'RB-',
            )
        );
    }
}
