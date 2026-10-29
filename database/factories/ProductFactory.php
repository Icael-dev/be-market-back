<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'sku' => strtoupper(fake()->bothify('SKU-???-####')),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(5000, 500000),
            'compare_price' => fake()->numberBetween(5000, 600000),
            'stock' => fake()->numberBetween(0, 100),
            'category_id' => Category::factory(),
            'images' => [],
            'specs' => [],
            'is_featured' => fake()->boolean(20),
            'is_active' => true,
            'condition' => fake()->randomElement(['new', 'used']),
            'weight' => fake()->randomFloat(2, 0.1, 50),
        ];
    }
}
