<?php

namespace Database\Factories;

use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItcPackage>
 */
class ItcPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => 'ITC-' . $this->faker->uuid(),
            'month_profit_percent' => 8.2,
            'created_at' => now(),
            'updated_at' => now(),
            'type' => PackageTypeEnum::STANDARD,
            'work_to' => now()->addMonths(6),
        ];
    }
}
