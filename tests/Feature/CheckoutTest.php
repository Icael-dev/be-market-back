<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\CreatesPassportClient;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesPassportClient;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPersonalAccessClient();
    }

    private function makeProduct(int $stock = 5): Product
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
            'price' => 1000,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(5);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/checkout', [
            'shipping_address' => [
                'full_name' => 'Jane',
                'phone' => '90000000',
                'address_line1' => '123 St',
                'city' => 'Lome',
                'country' => 'Togo',
            ],
            'payment_method' => 'cash_on_delivery',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        $this->assertEquals(3, $product->fresh()->stock);
        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    public function test_coupon_can_only_be_used_once_per_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(20);
        $coupon = Coupon::create([
            'id' => (string) Str::uuid(),
            'code' => 'ONCE',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'min_order_amount' => 0,
            'uses_count' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
        ]);

        Passport::actingAs($user);

        $address = [
            'full_name' => 'Jane',
            'phone' => '90000000',
            'address_line1' => '123 St',
            'city' => 'Lome',
            'country' => 'Togo',
        ];

        $first = Cart::create(['user_id' => $user->id]);
        $first->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson('/api/checkout', [
            'shipping_address' => $address,
            'payment_method' => 'cash_on_delivery',
            'coupon_code' => 'ONCE',
        ])->assertStatus(201);

        $second = Cart::create(['user_id' => $user->id]);
        $second->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson('/api/checkout', [
            'shipping_address' => $address,
            'payment_method' => 'cash_on_delivery',
            'coupon_code' => 'ONCE',
        ])->assertStatus(400);
    }

    public function test_checkout_with_empty_cart_is_rejected(): void
    {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $this->postJson('/api/checkout', [
            'shipping_address' => [
                'full_name' => 'Jane',
                'phone' => '90000000',
                'address_line1' => '123 St',
                'city' => 'Lome',
                'country' => 'Togo',
            ],
            'payment_method' => 'cash_on_delivery',
        ])->assertStatus(400);
    }
}
