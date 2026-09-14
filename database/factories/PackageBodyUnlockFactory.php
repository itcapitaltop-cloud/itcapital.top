<?php

namespace Database\Factories;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackageBodyUnlock>
 */
class PackageBodyUnlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unlockedAt = now();

        return [
            'uuid' => 'PBU-' . Str::random(10),
            'amount' => '100.00',
            'unlocked_at' => $unlockedAt,
            'payout_at' => $unlockedAt->copy()->addMonthNoOverflow(),
            'created_at' => $unlockedAt,
            'updated_at' => $unlockedAt,
        ];
    }

    /**
     * The one-month waiting period has elapsed, so the scheduler must pay this unlock out.
     */
    public function duePayout(?CarbonInterface $payoutAt = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'unlocked_at' => now()->subMonthNoOverflow(),
            'payout_at' => $payoutAt ?? now()->subMinute(),
        ]);
    }

    /**
     * The money already reached the MAIN balance through the given transaction.
     */
    public function paid(string $transactionUuid): static
    {
        return $this->state(fn (array $attributes): array => [
            'payout_transaction_uuid' => $transactionUuid,
        ]);
    }

    /**
     * The unlock was settled by closing the package and must never be paid out.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cancelled_at' => now(),
        ]);
    }
}
