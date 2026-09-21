<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'order_number' => 'ORD-'.strtoupper(fake()->unique()->bothify('########')),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'payment_method' => fake()->randomElement(['tmoney', 'flooz', 'card', 'cash_on_delivery']),
            'subtotal' => fake()->numberBetween(10000, 200000),
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'total' => fake()->numberBetween(10000, 200000),
            'shipping_address' => [
                'full_name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => 'Togo',
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
