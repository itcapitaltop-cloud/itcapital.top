<?php

namespace Database\Factories;

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, 10, 99999999),
            'user_id' => $this->faker->numberBetween(1, 10),
            'balance_type' => BalanceTypeEnum::MAIN,
            'trx_type' => TrxTypeEnum::DEPOSIT,
            'accepted_at' => $this->faker->dateTime(),
        ];
    }
}
