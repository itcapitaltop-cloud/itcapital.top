<?php

namespace Database\Factories;

use App\Models\PartnerRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerRank>
 */
class PartnerRankFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rank' => 1,
            'bonus_usd' => 0.0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
