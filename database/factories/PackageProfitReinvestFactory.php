<?php

namespace Database\Factories;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackageProfitReinvest>
 */
class PackageProfitReinvestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Срок заморозки по умолчанию — те же 180 дней, что ставит кабинет
     * (`App\Livewire\Account\Itc\Packages::reinvest()`), то есть реинвест ещё не созрел.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = now();

        return [
            'uuid' => 'PPR-' . Str::random(10),
            'amount' => '100.00',
            'matured_at' => $createdAt->copy()->addDays(180),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Срок заморозки истёк — реинвест можно снять.
     */
    public function matured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'matured_at' => now()->subDay(),
        ]);
    }

    /**
     * Срок заморозки ещё идёт — кнопка снятия такой реинвест не видит.
     */
    public function notMatured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'matured_at' => now()->addDays(180),
        ]);
    }

    /**
     * Легаси-строка без даты разморозки: проверяет ветку COALESCE(matured_at, created_at)
     * в скоупе `PackageProfitReinvest::unlockable()`.
     */
    public function legacy(): static
    {
        return $this->state(fn (array $attributes): array => [
            'matured_at' => null,
        ]);
    }

    /**
     * Пользователь снял реинвест: он уже вышел из базы начисления и ждёт выплаты.
     */
    public function unlocked(): static
    {
        $unlockedAt = now();

        return $this->state(fn (array $attributes): array => [
            'matured_at' => $unlockedAt->copy()->subDay(),
            'unlocked_at' => $unlockedAt,
            'payout_at' => $unlockedAt->copy()->addMonthNoOverflow(),
        ]);
    }

    /**
     * Календарный месяц ожидания истёк — команда выплаты обязана забрать эту строку.
     */
    public function duePayout(?CarbonInterface $payoutAt = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'matured_at' => now()->subMonthNoOverflow()->subDay(),
            'unlocked_at' => now()->subMonthNoOverflow(),
            'payout_at' => $payoutAt ?? now()->subMinute(),
        ]);
    }
}
