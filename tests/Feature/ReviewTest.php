<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\CreatesPassportClient;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use CreatesPassportClient;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPersonalAccessClient();
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
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_user_can_submit_a_review(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Passport::actingAs($user);

        $response = $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great',
            'comment' => 'Loved it',
        ]);

        $response->assertStatus(201);
        $review = Review::first();
        $this->assertFalse($review->is_verified_purchase);
    }

    public function test_review_is_verified_when_user_purchased_the_product(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Passport::actingAs($user);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST',
            'user_id' => $user->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 1000,
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'total' => 1000,
            'shipping_address' => ['full_name' => 'Jane'],
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'price' => 1000,
            'quantity' => 1,
            'total' => 1000,
        ]);

        $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'rating' => 4,
        ])->assertStatus(201);

        $this->assertTrue(Review::first()->is_verified_purchase);
    }

    public function test_review_requires_a_valid_rating(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Passport::actingAs($user);

        $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'rating' => 9,
        ])->assertStatus(422);
    }
}
