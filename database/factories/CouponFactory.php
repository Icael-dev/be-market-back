<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'code' => strtoupper(fake()->unique()->lexify('COUPON-???')),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->numberBetween(10, 50),
            'min_order_amount' => fake()->numberBetween(10000, 50000),
            'max_uses' => fake()->numberBetween(10, 100),
            'uses_count' => 0,
            'is_active' => true,
            'starts_at' => now()->subDays(1),
            'expires_at' => now()->addDays(30),
        ];
    }
}
