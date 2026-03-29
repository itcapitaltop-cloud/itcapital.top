<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ItcPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StakingPurchase>
 */
class StakingPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountUsd = $this->faker->randomFloat(2, 100, 5000);
        $purchaseRate = $this->faker->randomFloat(6, 0.05, 0.25);

        return [
            'itc_package_id' => ItcPackage::factory(),
            'user_id' => User::factory(),
            'amount_usd' => $amountUsd,
            'token_amount' => round($amountUsd / $purchaseRate, 2),
            'purchase_rate' => $purchaseRate,
            'purchased_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
