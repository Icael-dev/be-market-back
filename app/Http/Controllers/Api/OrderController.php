<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(private CouponService $coupons, private OrderService $orders) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $data = $request->only([
            'shipping_address',
            'billing_address',
            'payment_method',
            'notes',
            'coupon_code',
            'loyalty_points_to_redeem',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (! $cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        $items = $cart->items()->with('product.activeFlashSale')->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 400);
        }

        $subtotal = 0;
        foreach ($items as $item) {
            if (! $item->product || ! $item->product->is_active || $item->product->stock < $item->quantity) {
                return response()->json(['message' => 'One or more items are unavailable.'], 400);
            }
            $subtotal += $item->product->effectivePriceValue() * $item->quantity;
        }
        $subtotal = round($subtotal, 2);
        $userId = $request->user()->id;

        $discount = 0;
        $couponId = null;
        if (! empty($data['coupon_code'])) {
            $result = $this->coupons->validate($data['coupon_code'], $subtotal, $userId);

            if (! $result['valid']) {
                return response()->json(['message' => $result['message']], 400);
            }

            $couponId = $result['coupon']->id;
            $discount = $result['discount'];
        }

        $loyaltyDiscount = 0;
        $loyaltyPointsRedeemed = 0;
        $requestedPoints = (int) ($data['loyalty_points_to_redeem'] ?? 0);

        $threshold = (int) StoreSettings::resolve()['loyalty_points_threshold'];
        if ($threshold > 0 && ($subtotal - $discount) < $threshold) {
            return response()->json([
                'message' => "Un seuil de depense de {$threshold} FCFA est requis pour utiliser vos points.",
            ], 422);
        }

        if ($requestedPoints > 0) {
            $loyaltyPoints = LoyaltyPoints::firstOrCreate(
                ['user_id' => $userId],
                ['points' => 0, 'total_earned' => 0, 'total_redeemed' => 0]
            );

            $maxRedeemablePoints = (int) floor(($subtotal - $discount) * 4); // 1 point = 25 FCFA
            $pointsToRedeem = min($requestedPoints, $loyaltyPoints->points, $maxRedeemablePoints);

            if ($pointsToRedeem > 0) {
                $loyaltyDiscount = round($pointsToRedeem * 25, 2);
                $loyaltyPointsRedeemed = $pointsToRedeem;
            }
        }

        $shippingFee = 0;
        $tax = round(($subtotal - $discount - $loyaltyDiscount) * 0.1, 2);
        $total = round($subtotal - $discount - $loyaltyDiscount + $shippingFee + $tax, 2);

        $paymentMethod = $data['payment_method'] ?? 'cash_on_delivery';

        $order = $this->orders->place([
            'shipping_address' => $data['shipping_address'],
            'billing_address' => $data['billing_address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'payment_method' => $paymentMethod,
            'shipping_fee' => $shippingFee,
            'tax' => $tax,
            'coupon_id' => $couponId,
            'loyalty_points_redeemed' => $loyaltyPointsRedeemed,
        ], $request->user(), $cart);

        $cart->items()->delete();

        return response()->json($order->load('items'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'payments'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('limit')) {
            return response()->json($query->limit((int) $request->query('limit'))->get());
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'payments'])
            ->where('id', $id)
            ->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if (! in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
            return response()->json(['message' => 'Cette commande ne peut plus etre annulee.'], 422);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Commande annulee.', 'order' => $order->load('items.product')]);
    }
}
