<?php

namespace Database\Factories;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCodeUsage>
 */
class PromoCodeUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'promo_code_id' => PromoCode::factory(),
            'user_id' => User::factory(),
            'package_definition_id' => fn (array $attributes) => PromoCode::query()->find($attributes['promo_code_id'])?->package_definition_id,
            'used_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forPromoCode(PromoCode $promoCode): static
    {
        return $this->state(fn (array $attributes) => [
            'promo_code_id' => $promoCode->id,
            'package_definition_id' => $promoCode->package_definition_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
