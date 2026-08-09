<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeProduct(int $stock = 10): Product
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
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function createOrderForUser(User $user, Product $product): Order
    {
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        return Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'status' => 'processing',
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

    public function test_user_can_initiate_payment(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        $this->actingAs($user);

        $response = $this->postJson('/api/payment/initiate', [
            'order_id' => $order->id,
            'payment_method' => 'tmoney',
            'phone_number' => '90000000',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'order_id',
                'payment_method',
                'amount',
                'status',
                'reference',
                'phone_number',
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'tmoney',
            'status' => 'paid',
        ]);
    }

    public function test_user_can_view_payment_history(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'flooz',
            'amount' => $order->total,
            'status' => 'paid',
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'phone_number' => '90000000',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/payment/history');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_payment_requires_valid_order(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);

        $response = $this->postJson('/api/payment/initiate', [
            'order_id' => (string) Str::uuid(),
            'payment_method' => 'tmoney',
        ]);

        $response->assertStatus(422);
    }
}
