<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->only(['shipping_address', 'billing_address', 'payment_method', 'notes']);

        $validator = Validator::make($data, [
            'shipping_address' => ['required', 'array'],
            'shipping_address.full_name' => ['required', 'string'],
            'shipping_address.phone' => ['required', 'string'],
            'shipping_address.address_line1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string'],
            'payment_method' => ['sometimes', 'nullable', 'string'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (!$cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        $items = $cart->items()->with('product')->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 400);
        }

        $subtotal = 0;
        foreach ($items as $item) {
            if (!$item->product || !$item->product->is_active || $item->product->stock < $item->quantity) {
                return response()->json(['message' => 'One or more items are unavailable.'], 400);
            }
            $subtotal += $item->product->price * $item->quantity;
        }

        $shippingFee = 0;
        $tax = round($subtotal * 0.1, 2);
        $total = round($subtotal + $shippingFee + $tax, 2);

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'user_id' => $request->user()->id,
            'status' => 'processing',
            'payment_status' => 'pending',
            'payment_method' => $data['payment_method'] ?? 'card',
            'subtotal' => $subtotal,
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

            $item->product->decrement('stock', $item->quantity);
        }

        $cart->items()->delete();

        return response()->json($order->load('items'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)->with('items')->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->with('items')->where('id', $id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order);
    }
}
