<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStatisticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->query('days', 30), 365));
        $start = now()->subDays($days)->startOfDay();
        $prevStart = $start->copy()->subDays($days);

        $orders = Order::with('items')
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get();
        $previousOrders = Order::whereBetween('created_at', [$prevStart, $start])->get();

        $totalRevenue = (float) $orders->where('payment_status', 'paid')->sum('total');
        $previousRevenue = (float) $previousOrders->where('payment_status', 'paid')->sum('total');
        $dailyRevenue = collect(range(0, $days))->map(function (int $offset) use ($start, $orders) {
            $date = $start->copy()->addDays($offset);
            $dailyOrders = $orders->filter(fn (Order $order) => $order->created_at->isSameDay($date));

            return [
                'date' => $date->format('d M'),
                'revenue' => (float) $dailyOrders->where('payment_status', 'paid')->sum('total'),
                'orders' => $dailyOrders->count(),
            ];
        })->values();

        $statusData = $orders->countBy('status')
            ->map(fn (int $value, string $name) => ['name' => ucfirst($name), 'value' => $value])
            ->values();

        $paymentData = $orders->whereNotNull('payment_method')
            ->countBy('payment_method')
            ->map(fn (int $value, string $name) => [
                'name' => match ($name) {
                    'tmoney' => 'Tmoney',
                    'flooz' => 'Flooz',
                    'cash_on_delivery' => 'Livraison',
                    default => ucfirst($name),
                },
                'value' => $value,
            ])
            ->values();

        return response()->json([
            'total_revenue' => (float) Order::where('payment_status', 'paid')->sum('total'),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_customers' => Profile::count(),
            'period_revenue' => $totalRevenue,
            'period_orders' => $orders->count(),
            'new_customers' => Profile::where('created_at', '>=', $start)->count(),
            'avg_order' => $orders->count() > 0 ? round($totalRevenue / $orders->count(), 2) : 0,
            'revenue_change' => $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'orders_change' => $previousOrders->count() > 0 ? round((($orders->count() - $previousOrders->count()) / $previousOrders->count()) * 100, 1) : 0,
            'daily_revenue' => $dailyRevenue,
            'status_data' => $statusData,
            'payment_data' => $paymentData,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $type = $request->query('type', 'orders');

        $data = match ($type) {
            'customers' => Profile::orderBy('created_at', 'desc')->get(['email', 'full_name', 'phone', 'created_at']),
            'products' => Product::orderBy('name')->get(['name', 'price', 'stock', 'is_active']),
            'newsletter' => NewsletterSubscriber::orderBy('subscribed_at', 'desc')->get(['email', 'is_active', 'subscribed_at']),
            default => Order::orderBy('created_at', 'desc')->get(['order_number', 'status', 'payment_status', 'total', 'created_at']),
        };

        return response()->json($data);
    }
}
