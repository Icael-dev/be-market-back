<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $slug = strtolower(trim($name));

        return [
            'id' => Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
