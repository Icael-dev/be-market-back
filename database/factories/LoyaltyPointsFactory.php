<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LoyaltyPointsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'points' => fake()->numberBetween(0, 5000),
            'total_earned' => fake()->numberBetween(0, 10000),
            'total_redeemed' => fake()->numberBetween(0, 5000),
        ];
    }
}
