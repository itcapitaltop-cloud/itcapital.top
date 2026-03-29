<?php

declare(strict_types=1);

namespace App\Services\Token;

use App\Models\TokenRate;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class TokenRateResolver
{
    public function __construct(
        private readonly GeneralSetting $generalSetting,
    ) {}

    public function currentRate(): float
    {
        return $this->rateForDate(now());
    }

    public function currentEffectiveFrom(): string
    {
        return $this->effectiveFromForDate(now());
    }

    public function earliestRate(): ?float
    {
        $rate = TokenRate::query()
            ->oldest('effective_from')
            ->value('rate');

        return $rate !== null ? (float) $rate : null;
    }

    public function earliestEffectiveFrom(): ?string
    {
        $effectiveFrom = TokenRate::query()
            ->oldest('effective_from')
            ->value('effective_from');

        return $effectiveFrom !== null ? Carbon::parse($effectiveFrom)->toDateString() : null;
    }

    public function rateForDate(CarbonInterface|string|null $date = null): float
    {
        $effectiveDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());

        $rate = TokenRate::query()
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->latest('effective_from')
            ->value('rate');

        return (float) ($rate ?? $this->generalSetting->exchange_rate_itc);
    }

    public function effectiveFromForDate(CarbonInterface|string|null $date = null): string
    {
        $effectiveDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());

        return (string) (
            TokenRate::query()
                ->whereDate('effective_from', '<=', $effectiveDate)
                ->latest('effective_from')
                ->value('effective_from')
            ?? now()->toDateString()
        );
    }

    public function upsertRate(CarbonInterface|string $effectiveFrom, float $rate, ?int $tokenRateId = null): TokenRate
    {
        $effectiveDate = $effectiveFrom instanceof CarbonInterface
            ? $effectiveFrom->toDateString()
            : Carbon::parse($effectiveFrom)->toDateString();

        if ($tokenRateId !== null) {
            $tokenRate = TokenRate::query()->findOrFail($tokenRateId);

            $duplicateExists = TokenRate::query()
                ->where('id', '!=', $tokenRate->id)
                ->whereDate('effective_from', $effectiveDate)
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Курс на эту дату уже существует.',
                ]);
            }

            $tokenRate->update([
                'effective_from' => $effectiveDate,
                'rate' => $rate,
            ]);
        } else {
            $tokenRate = TokenRate::query()->updateOrCreate(
                ['effective_from' => $effectiveDate],
                ['rate' => $rate]
            );
        }

        $this->syncCurrentRate();

        return $tokenRate;
    }

    public function deleteRate(int $tokenRateId): void
    {
        if (TokenRate::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'token_rate_id' => 'Нельзя удалить последнюю запись курса.',
            ]);
        }

        TokenRate::query()->findOrFail($tokenRateId)->delete();

        $this->syncCurrentRate();
    }

    private function syncCurrentRate(): void
    {
        $this->generalSetting->exchange_rate_itc = $this->currentRate();
        $this->generalSetting->save();
    }
}
