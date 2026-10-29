<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private CouponService $coupons) {}

    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['valid' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $result = $this->coupons->validate(
            $data['code'],
            (float) $data['subtotal'],
            (string) $user->id
        );

        if (! $result['valid']) {
            return response()->json(['valid' => false, 'message' => $result['message']], 200);
        }

        $coupon = $result['coupon'];
        $discount = $result['discount'];

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
