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
        $coupon = Coupon::create($request->validate([
            'code' => 'required|string|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'min_order_amount' => 'sometimes|nullable|numeric',
            'max_uses' => 'sometimes|nullable|integer',
            'starts_at' => 'sometimes|nullable|date',
            'expires_at' => 'sometimes|nullable|date|after:starts_at',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($coupon, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Coupon::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validate([
            'code' => 'sometimes|string|unique:coupons,code,' . $id,
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric',
            'min_order_amount' => 'sometimes|nullable|numeric',
            'max_uses' => 'sometimes|nullable|integer',
            'starts_at' => 'sometimes|nullable|date',
            'expires_at' => 'sometimes|nullable|date|after:starts_at',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($coupon);
    }

    public function destroy(string $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();
        return response()->json(['message' => 'Coupon deleted.']);
    }
}
