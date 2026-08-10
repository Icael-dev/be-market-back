<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function place(array $data, User $user, Cart $cart): Order
    {
        $items = $cart->items;
        $subtotal = 0;
        $discount = 0;
        $shippingFee = $data['shipping_fee'] ?? 0;
        $tax = $data['tax'] ?? 0;
        $paymentMethod = $data['payment_method'];
        $couponId = $data['coupon_id'] ?? null;
        $loyaltyPointsRedeemed = $data['loyalty_points_redeemed'] ?? 0;
        $loyaltyDiscount = 0;

        foreach ($items as $item) {
            $unitPrice = $item->product->effectivePriceValue();
            $subtotal += $unitPrice * $item->quantity;
        }

        if ($couponId) {
            $coupon = Coupon::where('id', $couponId)->first();
            if ($coupon && $coupon->is_active) {
                $discount = $coupon->discount_type === 'percentage'
                    ? round($subtotal * ($coupon->discount_value / 100), 2)
                    : min($coupon->discount_value, $subtotal);
            }
        }

        if ($loyaltyPointsRedeemed > 0) {
            $loyalty = LoyaltyPoints::where('user_id', $user->id)->lockForUpdate()->first();
            if ($loyalty && $loyalty->points >= $loyaltyPointsRedeemed) {
                $loyaltyDiscount = $loyaltyPointsRedeemed * 25;
                $subtotalAfterDiscount = $subtotal - $discount;
                if ($loyaltyDiscount > $subtotalAfterDiscount) {
                    $loyaltyDiscount = $subtotalAfterDiscount;
                }
            } else {
                $loyaltyPointsRedeemed = 0;
            }
        }

        $total = max(0, $subtotal - $discount - $loyaltyDiscount + $shippingFee + $tax);

        return DB::transaction(function () use (
            $items, $subtotal, $discount, $shippingFee, $tax, $total, $data, $user,
            $paymentMethod, $couponId, $loyaltyPointsRedeemed, $loyaltyDiscount
        ) {
            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $user->id,
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
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ]);
            }

            if ($loyaltyPointsRedeemed > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $user->id)->lockForUpdate()->first();
                $loyalty->points -= $loyaltyPointsRedeemed;
                $loyalty->total_redeemed += $loyaltyPointsRedeemed;
                $loyalty->save();

                LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'type' => 'redeemed',
                    'points' => -$loyaltyPointsRedeemed,
                    'description' => 'Utilisation lors de la commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);
            }

            $pointsEarned = (int) floor(($subtotal - $discount - $loyaltyDiscount) / 2500);
            if ($pointsEarned > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['points' => 0, 'total_earned' => 0, 'total_redeemed' => 0]
                );
                $loyalty->points += $pointsEarned;
                $loyalty->total_earned += $pointsEarned;
                $loyalty->points_expire_at = now()->addMonths(6);
                $loyalty->save();

                LoyaltyTransaction::create([
                    'user_id' => $user->id,
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
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            if ($order->loyalty_points_redeemed > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $order->user_id)->lockForUpdate()->first();
                if ($loyalty) {
                    $loyalty->points += $order->loyalty_points_redeemed;
                    $loyalty->total_redeemed -= $order->loyalty_points_redeemed;
                    $loyalty->save();
                }

                LoyaltyTransaction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => 'redeemed',
                    'points' => $order->loyalty_points_redeemed,
                    'description' => 'Remboursement pour annulation de la commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);
            }

            if ($order->loyalty_points_earned > 0) {
                $loyalty = LoyaltyPoints::where('user_id', $order->user_id)->lockForUpdate()->first();
                if ($loyalty) {
                    $loyalty->points -= $order->loyalty_points_earned;
                    $loyalty->total_earned -= $order->loyalty_points_earned;
                    $loyalty->save();
                }

                LoyaltyTransaction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => 'earned',
                    'points' => -$order->loyalty_points_earned,
                    'description' => 'Annulation des points pour la commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);
            }

            if ($order->discount > 0) {
                $couponUsage = CouponUsage::where('order_id', $order->id)->first();
                if ($couponUsage) {
                    $coupon = $couponUsage->coupon;
                    if ($coupon) {
                        $coupon->decrement('uses_count');
                    }
                    $couponUsage->delete();
                }
            }
        });
    }
}
