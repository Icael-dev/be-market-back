<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'id' => (string) Str::uuid(),
            'code' => 'TEST'.strtoupper(Str::random(5)),
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'min_order_amount' => 0,
            'uses_count' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ], $overrides));
    }

    public function test_validates_active_coupon(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon();

        $result = $service->validate($coupon->code, 1000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(100, $result['discount']);
    }

    public function test_rejects_inactive_coupon(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['is_active' => false]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_expired_coupon(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['expires_at' => now()->subDay()]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_future_coupon(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['starts_at' => now()->addDay()]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_coupon_below_minimum_order(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['min_order_amount' => 500]);

        $result = $service->validate($coupon->code, 100);

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_coupon_at_max_uses(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['max_uses' => 1, 'uses_count' => 1]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_coupon_already_used_by_user(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon();
        $user = User::factory()->create();

        $coupon->usages()->create(['user_id' => $user->id]);

        $result = $service->validate($coupon->code, 1000, $user->id);

        $this->assertFalse($result['valid']);
    }

    public function test_percentage_discount_is_calculated_correctly(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon([
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(100, $result['discount']);
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $service = new CouponService;
        $coupon = $this->makeCoupon(['discount_value' => 2000]);

        $result = $service->validate($coupon->code, 1000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(1000, $result['discount']);
    }
}
