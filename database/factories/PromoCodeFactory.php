<?php

namespace Database\Factories;

use App\Models\Package\PackageDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->bothify('ITC-????-####')),
            'package_definition_id' => PackageDefinition::factory(),
            'reduced_minimum_amount' => fake()->randomElement(['10.00000000', '25.00000000', '50.00000000']),
            'created_by_admin_id' => null,
        ];
    }

    public function forPackageDefinition(PackageDefinition $packageDefinition): static
    {
        return $this->state(fn (array $attributes) => [
            'package_definition_id' => $packageDefinition->id,
        ]);
    }
}
