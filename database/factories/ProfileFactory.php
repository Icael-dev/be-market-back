<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        $user = User::factory();

        return [
            'id' => Str::uuid(),
            'user_id' => $user,
            'email' => $user->email,
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'avatar_url' => null,
            'is_admin' => false,
            'role' => 'client',
        ];
    }
}
