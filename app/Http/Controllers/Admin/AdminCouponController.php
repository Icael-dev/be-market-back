<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Coupon::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('discount_type') === 'percentage' && $value > 100) {
                    $fail('Percentage discount cannot exceed 100.');
                }
            }],
            'min_order_amount' => 'sometimes|nullable|numeric',
            'max_uses' => 'sometimes|nullable|integer',
            'starts_at' => 'sometimes|nullable|date',
            'expires_at' => 'sometimes|nullable|date|after:starts_at',
            'is_active' => 'sometimes|boolean',
        ]);

        $coupon = Coupon::create($data);

        return response()->json($coupon, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Coupon::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $data = $request->validate([
            'code' => 'sometimes|string|unique:coupons,code,'.$id,
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => ['sometimes', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('discount_type') === 'percentage' && $value > 100) {
                    $fail('Percentage discount cannot exceed 100.');
                }
            }],
            'min_order_amount' => 'sometimes|nullable|numeric',
            'max_uses' => 'sometimes|nullable|integer',
            'starts_at' => 'sometimes|nullable|date',
            'expires_at' => 'sometimes|nullable|date|after:starts_at',
            'is_active' => 'sometimes|boolean',
        ]);

        $coupon->update($data);

        return response()->json($coupon);
    }

    public function destroy(string $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }
}
