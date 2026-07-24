<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService
{
    public function validate(string $code, float $subtotal, ?string $userId = null): array
    {
        $coupon = Coupon::where('code', strtoupper($code))
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }

        $now = now();
        if ($coupon->starts_at && $coupon->starts_at > $now) {
            return ['valid' => false, 'message' => 'Coupon is not yet active.'];
        }

        if ($coupon->expires_at && $coupon->expires_at < $now) {
            return ['valid' => false, 'message' => 'Coupon has expired.'];
        }

        $coupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();

        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return ['valid' => false, 'message' => 'Coupon has reached maximum uses.'];
        }

        if ($userId) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->exists();
            if ($userUsage) {
                return ['valid' => false, 'message' => 'You have already used this coupon.'];
            }
        }

        if ($subtotal < ($coupon->min_order_amount ?? 0)) {
            return ['valid' => false, 'message' => "Minimum order amount for this coupon is {$coupon->min_order_amount}."];
        }

        if ($coupon->discount_type === 'percentage') {
            $pct = min(100, max(0, (float) $coupon->discount_value));
            if ($pct <= 0) {
                return ['valid' => false, 'message' => 'Invalid coupon discount.'];
            }
            $discount = round($subtotal * ($pct / 100), 2);
        } else {
            $fixed = min((float) $coupon->discount_value, $subtotal);
            $discount = round(max(0, $fixed), 2);
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }
}
