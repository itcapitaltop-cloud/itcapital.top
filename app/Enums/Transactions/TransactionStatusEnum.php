<?php

namespace App\Enums\Transactions;

use Illuminate\Support\Carbon;

enum TransactionStatusEnum: string
{
    case MODERATE = 'moderate';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public static function fromDates(null|Carbon|string $acceptedAt, null|Carbon|string $rejectedAt): self
    {
        if (!is_null($acceptedAt)) {
            return self::ACCEPTED;
        }

        if (!is_null($rejectedAt)) {
            return self::REJECTED;
        }

        return self::MODERATE;
    }

    public function checkStatus(TransactionStatusEnum $status): bool
    {
        return $this === $status;
    }

    public function getName(): string
    {
        return match ($this) {
            self::ACCEPTED => 'Исполнено',
            self::MODERATE => 'На модерации',
            self::REJECTED => 'Отклонено'
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACCEPTED => 'green',
            self::MODERATE => 'yellow',
            self::REJECTED => 'red'
        };
    }
}
