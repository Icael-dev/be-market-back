<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'label' => fake()->optional()->word(),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => 'Lomé',
            'region' => 'Maritime',
            'country' => 'Togo',
            'postal_code' => fake()->optional()->postcode(),
            'is_default' => fake()->boolean(30),
        ];
    }
}
