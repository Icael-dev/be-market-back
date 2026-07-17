<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->only(['shipping_address', 'billing_address', 'payment_method', 'notes', 'coupon_code']);

        $validator = Validator::make($data, [
            'shipping_address' => ['required', 'array'],
            'shipping_address.full_name' => ['required', 'string'],
            'shipping_address.phone' => ['required', 'string'],
            'shipping_address.address_line1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'in:tmoney,flooz,cash_on_delivery'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'coupon_code' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (! $cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        $items = $cart->items()->with('product')->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 400);
        }

        $subtotal = 0;
        foreach ($items as $item) {
            if (! $item->product || ! $item->product->is_active || $item->product->stock < $item->quantity) {
                return response()->json(['message' => 'One or more items are unavailable.'], 400);
            }
            $subtotal += $item->product->price * $item->quantity;
        }

        $discount = 0;
        $couponId = null;
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])
                ->where('is_active', true)
                ->first();

            if (! $coupon) {
                return response()->json(['message' => 'Invalid coupon code.'], 400);
            }

            $now = now();
            if ($coupon->starts_at && $coupon->starts_at > $now) {
                return response()->json(['message' => 'Coupon is not yet active.'], 400);
            }

            if ($coupon->expires_at && $coupon->expires_at < $now) {
                return response()->json(['message' => 'Coupon has expired.'], 400);
            }

            if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
                return response()->json(['message' => 'Coupon has reached maximum uses.'], 400);
            }

            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $request->user()->id)
                ->exists();
            if ($userUsage) {
                return response()->json(['message' => 'You have already used this coupon.'], 400);
            }

            if ($subtotal < ($coupon->min_order_amount ?? 0)) {
                return response()->json(['message' => "Minimum order amount for this coupon is {$coupon->min_order_amount}."], 400);
            }

            if ($coupon->discount_type === 'percentage') {
                $discount = round($subtotal * ($coupon->discount_value / 100), 2);
            } else {
                $discount = $coupon->discount_value;
            }

            $couponId = $coupon->id;
        }

        $shippingFee = 0;
        $tax = round(($subtotal - $discount) * 0.1, 2);
        $total = round($subtotal - $discount + $shippingFee + $tax, 2);

        $userId = $request->user()->id;
        $paymentMethod = $data['payment_method'] ?? 'cash_on_delivery';

        $order = DB::transaction(function () use ($items, $subtotal, $discount, $shippingFee, $tax, $total, $data, $userId, $paymentMethod, $couponId) {
            $order = Order::create([
                'order_number' => 'ORD-'.strtoupper(uniqid()),
                'user_id' => $userId,
                'status' => 'processing',
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'total' => $total,
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'total' => round($item->product->price * $item->quantity, 2),
                ]);

                $product = $item->product->fresh();
                if (! $product || $product->stock < $item->quantity) {
                    throw new \RuntimeException('One or more items are no longer available.');
                }
                $product->decrement('stock', $item->quantity);
            }

            if ($couponId) {
                Coupon::where('id', $couponId)->increment('uses_count');
                CouponUsage::create([
                    'coupon_id' => $couponId,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                ]);
            }

            return $order;
        });

        $cart->items()->delete();

        return response()->json($order->load('items'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::where('user_id', $request->user()->id)->with('items')->orderBy('created_at', 'desc');

        if ($request->filled('limit')) {
            return response()->json($query->limit((int) $request->query('limit'))->get());
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->with('items')->where('id', $id)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order);
    }
}
