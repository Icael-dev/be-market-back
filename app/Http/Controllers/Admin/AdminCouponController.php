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
            'type' => 'required|string',
            'value' => 'required|numeric',
            'minimum_amount' => 'sometimes|nullable|numeric',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'usage_limit' => 'sometimes|nullable|integer',
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
            'type' => 'sometimes|string',
            'value' => 'sometimes|numeric',
            'minimum_amount' => 'sometimes|nullable|numeric',
            'starts_at' => 'sometimes|date',
            'expires_at' => 'sometimes|date|after:starts_at',
            'usage_limit' => 'sometimes|nullable|integer',
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
