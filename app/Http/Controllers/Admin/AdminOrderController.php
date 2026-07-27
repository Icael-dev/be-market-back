<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::withTrashed()->with('user', 'items.product')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->paginate((int) $request->query('per_page', 10)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $order = Order::withTrashed()->with('user', 'items.product')->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,processing,paid,failed,refunded',
        ]);

        $previousStatus = $order->status;
        $order->update($data);

        if ($previousStatus !== 'cancelled' && $order->status === 'cancelled') {
            $this->reverseOrderEffects($order);
        }

        return response()->json($order->refresh()->load('user', 'items.product'));
    }

    public function restore(string $id): JsonResponse
    {
        $order = Order::withTrashed()->findOrFail($id);
        $order->restore();

        return response()->json(['message' => 'Order restored.', 'order' => $order->load('user', 'items.product')]);
    }

    private function reverseOrderEffects(Order $order): void
    {
        DB::transaction(function () use ($order) {
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
                    'description' => 'Remboursement suite à annulation de la commande '.$order->order_number,
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
                    'description' => 'Annulation des points gagnés pour la commande '.$order->order_number,
                    'reference' => $order->order_number,
                ]);
            }

            CouponUsage::where('order_id', $order->id)->delete();

            if ($order->discount > 0) {
                \App\Models\Coupon::whereHas('usages', function ($q) use ($order) {
                    $q->where('order_id', $order->id);
                })->decrement('uses_count');
            }

            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }
        });
    }
}
