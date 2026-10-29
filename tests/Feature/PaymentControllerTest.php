<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_user_can_initiate_payment_sandbox(): void
    {
        config(['app.env' => 'local']);
        config(['payment.sandbox' => true]);

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
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'tmoney',
            'phone_number' => '90000000',
            'status' => 'paid',
        ]);
    }

    public function test_user_can_initiate_payment_fedapay_production(): void
    {
        $this->app['env'] = 'production';
        config(['payment.sandbox' => false]);

        config([
            'services.fedapay.api_key' => 'test-api-key',
            'services.fedapay.base_url' => 'https://api.fedapay.com/v1',
            'services.fedapay.callback_url' => 'https://example.com/callback',
        ]);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        Http::fake([
            'https://api.fedapay.com/v1/transactions' => Http::response([
                'v1/transaction' => [
                    'id' => 7450,
                    'reference' => 'ORDER-'.$order->id.'-TEST123',
                    'amount' => 5000,
                    'description' => 'Order #'.Str::random(8),
                    'status' => 'pending',
                    'callback_url' => 'https://example.com/callback',
                    'mode' => 'togocel',
                ],
            ], 201),
        ]);

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
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'tmoney',
            'phone_number' => '90000000',
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fedapay.com/v1/transactions'
                && $request->hasHeader('Authorization');
        });
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

    public function test_verify_payment_sandbox(): void
    {
        $this->app['env'] = 'local';
        config(['payment.sandbox' => true]);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'tmoney',
            'amount' => $order->total,
            'status' => 'paid',
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'phone_number' => '90000000',
            'paid_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->getJson("/api/payment/verify/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'order_id',
                'payment_method',
                'amount',
                'status',
                'reference',
            ]);
    }

    public function test_verify_payment_fedapay_confirms_with_provider(): void
    {
        $this->app['env'] = 'production';
        config(['payment.sandbox' => false]);
        config(['payment.callback_secret' => 'test-secret']);

        config([
            'services.fedapay.api_key' => 'test-api-key',
            'services.fedapay.base_url' => 'https://api.fedapay.com/v1',
            'services.fedapay.callback_url' => 'https://example.com/callback',
        ]);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'tmoney',
            'amount' => $order->total,
            'status' => 'pending',
            'reference' => 'ORDER-'.$order->id.'-TEST123',
            'phone_number' => '90000000',
            'provider_response' => ['id' => 7450, 'status' => 'pending'],
        ]);

        Http::fake([
            'https://api.fedapay.com/v1/transactions/ORDER-*' => Http::response([
                'v1/transaction' => [
                    'id' => 7450,
                    'reference' => 'ORDER-'.$order->id.'-TEST123',
                    'amount' => 5000,
                    'status' => 'approved',
                    'approved_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $this->actingAs($user);

        $response = $this->getJson("/api/payment/verify/{$payment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);

        Http::assertSent(function ($request) use ($payment) {
            return str_contains($request->url(), '/transactions/')
                && str_contains($request->url(), 'TEST123');
        });
    }

    public function test_callback_handles_fedapay_webhook(): void
    {
        $this->app['env'] = 'production';
        config(['payment.sandbox' => false]);
        config(['payment.callback_secret' => 'test-secret']);

        config([
            'services.fedapay.api_key' => 'test-api-key',
            'services.fedapay.base_url' => 'https://api.fedapay.com/v1',
            'services.fedapay.callback_url' => 'https://example.com/callback',
        ]);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'tmoney',
            'amount' => $order->total,
            'status' => 'pending',
            'reference' => 'ORDER-'.$order->id.'-TEST123',
            'phone_number' => '90000000',
            'provider_response' => ['id' => 7450, 'status' => 'pending'],
        ]);

        Http::fake([
            'https://api.fedapay.com/v1/transactions/ORDER-*' => Http::response([
                'v1/transaction' => [
                    'id' => 7450,
                    'reference' => 'ORDER-'.$order->id.'-TEST123',
                    'amount' => 5000,
                    'status' => 'approved',
                    'approved_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'X-Callback-Secret' => 'test-secret',
        ])->postJson('/api/payment/callback', [
            'reference' => $payment->reference,
            'status' => 'paid',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_callback_requires_valid_secret_in_production(): void
    {
        config(['app.env' => 'production']);
        config(['payment.callback_secret' => 'correct-secret']);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'tmoney',
            'amount' => $order->total,
            'status' => 'pending',
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'phone_number' => '90000000',
        ]);

        $response = $this->postJson('/api/payment/callback', [
            'reference' => 'PAY-TEST123',
            'status' => 'paid',
        ]);

        $response->assertStatus(401);
    }

    public function test_callback_idempotent(): void
    {
        $this->app['env'] = 'production';
        config(['payment.sandbox' => false]);
        config(['payment.callback_secret' => 'test-secret']);

        config([
            'services.fedapay.api_key' => 'test-api-key',
            'services.fedapay.base_url' => 'https://api.fedapay.com/v1',
            'services.fedapay.callback_url' => 'https://example.com/callback',
        ]);

        $user = $this->makeUser();
        $product = $this->makeProduct();
        $order = $this->createOrderForUser($user, $product);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'tmoney',
            'amount' => $order->total,
            'status' => 'pending',
            'reference' => 'ORDER-'.$order->id.'-TEST123',
            'phone_number' => '90000000',
            'provider_response' => ['id' => 7450, 'status' => 'pending'],
        ]);

        Http::fake([
            'https://api.fedapay.com/v1/transactions/ORDER-*' => Http::response([
                'v1/transaction' => [
                    'id' => 7450,
                    'reference' => 'ORDER-'.$order->id.'-TEST123',
                    'amount' => 5000,
                    'status' => 'approved',
                    'approved_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $this->withHeaders([
            'X-Callback-Secret' => 'test-secret',
        ])->postJson('/api/payment/callback', [
            'reference' => $payment->reference,
            'status' => 'paid',
        ])->assertStatus(200);

        $this->withHeaders([
            'X-Callback-Secret' => 'test-secret',
        ])->postJson('/api/payment/callback', [
            'reference' => $payment->reference,
            'status' => 'paid',
        ])->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }
}
