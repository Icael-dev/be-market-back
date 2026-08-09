<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
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

    private function makeOrder(string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        $user = User::factory()->create();
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Phones',
            'slug' => 'phones-'.Str::random(6),
            'is_active' => true,
        ]);
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.Str::random(6),
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 1000,
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'total' => 1000,
            'shipping_address' => [
                'full_name' => 'Test',
                'phone' => '90000000',
                'address_line1' => '123 St',
                'city' => 'Lome',
                'country' => 'Togo',
            ],
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'price' => 1000,
            'quantity' => 1,
            'total' => 1000,
        ]);

        return $order;
    }

    public function test_admin_can_list_orders(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder();

        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'payment_status', 'total'],
                ],
            ]);
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('pending');

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'status' => 'shipped',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'shipped');
    }

    public function test_admin_can_cancel_order_and_stock_is_restored(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('pending');
        $product = $order->items->first()->product;
        $initialStock = $product->stock;

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');

        $this->assertEquals($initialStock + 1, $product->fresh()->stock);
    }

    public function test_admin_can_restore_cancelled_order(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder('cancelled');

        $this->actingAs($admin);

        $response = $this->postJson("/api/admin/orders/{$order->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('order.status', 'processing');
    }
}
