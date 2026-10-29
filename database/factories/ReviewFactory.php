<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $user = User::factory();
        $product = Product::factory();

        return [
            'id' => Str::uuid(),
            'product_id' => $product,
            'user_id' => $user,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
            'is_verified_purchase' => fake()->boolean(70),
        ];
    }
}
