<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::where('code', strtoupper($data['code']))
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return response()->json(['valid' => false, 'message' => 'Invalid coupon code.'], 200);
        }

        $now = now();
        if ($coupon->starts_at && $coupon->starts_at > $now) {
            return response()->json(['valid' => false, 'message' => 'Coupon is not yet active.'], 200);
        }

        if ($coupon->expires_at && $coupon->expires_at < $now) {
            return response()->json(['valid' => false, 'message' => 'Coupon has expired.'], 200);
        }

        $coupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();

        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Coupon has reached maximum uses.'], 200);
        }

        $userId = $request->user()->id;
        $userUsage = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->exists();
        if ($userUsage) {
            return response()->json(['valid' => false, 'message' => 'You have already used this coupon.'], 200);
        }

        $subtotal = (float) $data['subtotal'];
        if ($subtotal < ($coupon->min_order_amount ?? 0)) {
            return response()->json(['valid' => false, 'message' => "Minimum order amount for this coupon is {$coupon->min_order_amount}."], 200);
        }

        if ($coupon->discount_type === 'percentage') {
            $pct = min(100, max(0, (float) $coupon->discount_value));
            $discount = round($subtotal * ($pct / 100), 2);
        } else {
            $fixed = min((float) $coupon->discount_value, $subtotal);
            $discount = round(max(0, $fixed), 2);
        }

        return response()->json([
            'valid' => true,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'discount_amount' => $discount,
            'message' => "Coupon applied successfully! You save {$discount} FCFA.",
        ]);
    }
}
