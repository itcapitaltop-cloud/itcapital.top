<?php

declare(strict_types=1);

namespace App\Tasks\Transaction;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class CreateTransactionTask
{
    private string $prefix = 'ITC-';

    private TrxTypeEnum $trxType = TrxTypeEnum::BUY_PACKAGE;

    private BalanceTypeEnum $balanceType = BalanceTypeEnum::MAIN;

    private ?Carbon $acceptedAt = null;

    private ?Carbon $rejectedAt = null;

    public function run(string $amount, int $userId): Transaction
    {
        return Transaction::query()->create([
            'uuid' => $this->prefix . Str::random(10),
            'amount' => $amount,
            'trx_type' => $this->trxType,
            'balance_type' => $this->balanceType,
            'user_id' => $userId,
            'accepted_at' => $this->acceptedAt,
            'rejected_at' => $this->rejectedAt,
        ]);
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function trxType(TrxTypeEnum $trxType): self
    {
        $this->trxType = $trxType;

        return $this;
    }

    public function balanceType(BalanceTypeEnum $balanceType): self
    {
        $this->balanceType = $balanceType;

        return $this;
    }

    public function acceptedAt(Carbon $acceptedAt): self
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function rejectedAt(Carbon $rejectedAt): self
    {
        $this->rejectedAt = $rejectedAt;

        return $this;
    }
}
