<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LoyaltyTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'type' => fake()->randomElement(['earned', 'redeemed']),
            'points' => fake()->numberBetween(-500, 500),
            'description' => fake()->sentence(),
            'reference' => 'ORD-'.strtoupper(fake()->bothify('########')),
        ];
    }
}
