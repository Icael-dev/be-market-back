<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
        ]);
    }

    private function makeClient(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_admin' => false,
        ]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Phones',
            'slug' => 'phones-'.Str::random(6),
            'is_active' => true,
        ]);

        return Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.Str::random(6),
            'price' => 5000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    private function createOrder(User $user): Order
    {
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        return Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 5000,
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'total' => 5000,
            'shipping_address' => ['full_name' => 'Jane'],
        ]);
    }

    public function test_admin_can_list_orders(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $this->createOrder($client);

        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $order = $this->createOrder($client);

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'confirmed');
    }

    public function test_non_admin_cannot_access_admin_orders(): void
    {
        $client = $this->makeClient();
        $this->createOrder($client);

        $this->actingAs($client);

        $this->getJson('/api/admin/orders')
            ->assertStatus(403);
    }
}
