<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::where('is_active', true)->get();

        if ($categories->isEmpty()) {
            $this->command?->warn('No active categories found, skipping product seeding.');

            return;
        }

        $perCategory = max(1, (int) ceil(20 / $categories->count()));

        foreach ($categories as $category) {
            Product::factory()
                ->count($perCategory)
                ->create([
                    'category_id' => $category->id,
                ]);
        }
    }
}
