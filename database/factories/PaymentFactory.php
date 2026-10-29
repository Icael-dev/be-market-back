<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'payment_method' => fake()->randomElement(['tmoney', 'flooz', 'card', 'cash_on_delivery']),
            'amount' => fake()->numberBetween(10000, 200000),
            'phone_number' => fake()->phoneNumber(),
            'reference' => 'PAY-'.strtoupper(fake()->unique()->bothify('########')),
            'status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'paid_at' => fake()->optional()->dateTime(),
        ];
    }
}
