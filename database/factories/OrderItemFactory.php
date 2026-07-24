<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory();

        return [
            'id' => Str::uuid(),
            'order_id' => Order::factory(),
            'product_id' => $product,
            'product_name' => $product->name ?? fake()->words(2, true),
            'product_sku' => strtoupper(fake()->bothify('SKU-???-####')),
            'price' => fake()->numberBetween(5000, 100000),
            'quantity' => fake()->numberBetween(1, 3),
            'total' => fake()->numberBetween(5000, 300000),
        ];
    }
}
