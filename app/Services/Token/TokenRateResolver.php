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
    /**
     * Request-scoped memoization. Token rates are immutable within a single
     * request, so resolving the same date repeatedly (e.g. one lookup per
     * staking package on the dashboard) must not hit the database each time.
     */
    private bool $earliestResolved = false;

    private ?float $earliestRate = null;

    private ?string $earliestEffectiveFrom = null;

    /**
     * @var array<string, float>
     */
    private array $rateForDateCache = [];

    /**
     * @var array<string, string>
     */
    private array $effectiveFromForDateCache = [];

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
        $this->resolveEarliest();

        return $this->earliestRate;
    }

    public function earliestEffectiveFrom(): ?string
    {
        $this->resolveEarliest();

        return $this->earliestEffectiveFrom;
    }

    public function rateForDate(CarbonInterface|string|null $date = null): float
    {
        $effectiveDate = $this->normalizeDate($date);

        return $this->rateForDateCache[$effectiveDate] ??= (function () use ($effectiveDate): float {
            $rate = TokenRate::query()
                ->whereDate('effective_from', '<=', $effectiveDate)
                ->latest('effective_from')
                ->value('rate');

            return (float) ($rate ?? $this->generalSetting->exchange_rate_itc);
        })();
    }

    public function effectiveFromForDate(CarbonInterface|string|null $date = null): string
    {
        $effectiveDate = $this->normalizeDate($date);

        return $this->effectiveFromForDateCache[$effectiveDate] ??= (string) (
            TokenRate::query()
                ->whereDate('effective_from', '<=', $effectiveDate)
                ->latest('effective_from')
                ->value('effective_from')
            ?? now()->toDateString()
        );
    }

    private function normalizeDate(CarbonInterface|string|null $date): string
    {
        return $date instanceof CarbonInterface
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());
    }

    private function resolveEarliest(): void
    {
        if ($this->earliestResolved) {
            return;
        }

        $earliest = TokenRate::query()
            ->oldest('effective_from')
            ->first(['rate', 'effective_from']);

        $this->earliestRate = $earliest?->rate !== null ? (float) $earliest->rate : null;
        $this->earliestEffectiveFrom = $earliest?->effective_from !== null
            ? Carbon::parse($earliest->effective_from)->toDateString()
            : null;
        $this->earliestResolved = true;
    }

    private function flushCache(): void
    {
        $this->earliestResolved = false;
        $this->earliestRate = null;
        $this->earliestEffectiveFrom = null;
        $this->rateForDateCache = [];
        $this->effectiveFromForDateCache = [];
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
        $this->flushCache();

        $this->generalSetting->exchange_rate_itc = $this->currentRate();
        $this->generalSetting->save();
    }
}
