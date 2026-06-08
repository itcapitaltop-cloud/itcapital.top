<?php

namespace Database\Factories;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PackageDefinition>
 */
class PackageDefinitionFactory extends Factory
{
    protected $model = PackageDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'type' => PackageTypeEnum::STANDARD,
            'name' => 'Standard',
            'default_profit_percent' => '8.20',
            'min_start_amount' => '250.00000000',
            'duration_months' => 6,
            'card_image_path' => null,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    public function forPackageType(PackageTypeEnum $packageType): static
    {
        return $this->state(fn (array $attributes): array => [
            'slug' => $packageType->value,
            'type' => $packageType,
            'name' => $packageType->getName(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
