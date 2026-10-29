<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $slug = 'phones'): Category
    {
        return Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Phones',
            'slug' => $slug.'-'.Str::random(6),
            'is_active' => true,
        ]);
    }

    private function makeProduct(int $stock = 10, ?string $categoryId = null): Product
    {
        $category = $categoryId ? Category::find($categoryId) : $this->makeCategory();

        return Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Test Product '.Str::random(6),
            'slug' => 'test-product-'.Str::random(6),
            'price' => 1000,
            'compare_price' => 1500,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_public_can_list_products(): void
    {
        $this->makeProduct();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'price', 'stock', 'category_id'],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $category = $this->makeCategory('test-category');
        $this->makeProduct(10, $category->id);
        $this->makeProduct(10);

        $response = $this->getJson("/api/products?category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_inactive_products_are_excluded(): void
    {
        $active = $this->makeProduct();
        Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $active->category_id,
            'name' => 'Inactive Product',
            'slug' => 'inactive-product-'.Str::random(6),
            'price' => 500,
            'stock' => 5,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_limit_parameter_returns_array(): void
    {
        $this->makeProduct();
        $this->makeProduct();

        $response = $this->getJson('/api/products?limit=1');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_price_bounds_returns_min_max(): void
    {
        $this->makeProduct(10);
        $this->makeProduct(20);

        $response = $this->getJson('/api/products/price-bounds');

        $response->assertStatus(200)
            ->assertJsonStructure(['min', 'max'])
            ->assertJson([
                'min' => 1000,
                'max' => 1000,
            ]);
    }
}
