<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
        ]);
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Phones',
            'slug' => 'phones-'.Str::random(6),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();

        $this->actingAs($admin);

        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'price' => 1000,
            'stock' => 10,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'price',
                'stock',
                'category_id',
            ]);

        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Old Product',
            'slug' => 'old-product-'.Str::random(6),
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 1500,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Product');

        $this->assertDatabaseHas('products', ['name' => 'Updated Product']);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->makeCategory();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Delete Me',
            'slug' => 'delete-me-'.Str::random(6),
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
