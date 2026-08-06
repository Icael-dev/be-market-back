<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CouponService;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private CouponService $coupons) {}

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->only(['shipping_address', 'billing_address', 'payment_method', 'notes', 'coupon_code', 'loyalty_points_to_redeem']);

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
            'loyalty_points_to_redeem' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

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

        $order = DB::transaction(function () use ($items, $subtotal, $discount, $shippingFee, $tax, $total, $data, $userId, $paymentMethod, $couponId, $loyaltyDiscount, $loyaltyPointsRedeemed) {
            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
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
                'loyalty_points_redeemed' => $loyaltyPointsRedeemed,
                'loyalty_discount_amount' => $loyaltyDiscount,
            ]);

            foreach ($items as $item) {
                $unitPrice = $item->product->effectivePriceValue();
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'total' => round($unitPrice * $item->quantity, 2),
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

            if ($loyaltyPointsRedeemed > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $userId)->lockForUpdate()->first();
                $loyalty->points -= $loyaltyPointsRedeemed;
                $loyalty->total_redeemed += $loyaltyPointsRedeemed;
                $loyalty->save();

                LoyaltyTransaction::create([
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'redeemed',
                    'points' => -$loyaltyPointsRedeemed,
                    'description' => 'Utilisation lors de la commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);
            }

            $pointsEarned = (int) floor(($subtotal - $discount - $loyaltyDiscount) / 2500);
            if ($pointsEarned > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $userId)->lockForUpdate()->firstOrCreate(
                    ['user_id' => $userId],
                    ['points' => 0, 'total_earned' => 0, 'total_redeemed' => 0]
                );
                $loyalty->points += $pointsEarned;
                $loyalty->total_earned += $pointsEarned;
                $loyalty->points_expire_at = now()->addMonths(6);
                $loyalty->save();

                LoyaltyTransaction::create([
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'earned',
                    'points' => $pointsEarned,
                    'description' => 'Commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);

                $order->loyalty_points_earned = $pointsEarned;
                $order->save();
            }

            return $order;
        });

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
}
