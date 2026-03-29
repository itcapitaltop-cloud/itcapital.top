<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenRate>
 */
class TokenRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'effective_from' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'rate' => $this->faker->randomFloat(6, 0.01, 1),
        ];
    }
}
