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

        // Periods (inclusive start, exclusive end)
        $start = now()->subDays($days)->startOfDay();
        $end = $start->copy()->addDays($days + 1); // +1 to include $start..$start+$days
        $prevStart = $start->copy()->subDays($days + 1);
        $prevEnd = $start->copy();

        $paidOrdersBase = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid');

        $totalRevenue = (float) (clone $paidOrdersBase)->sum('total');
        $previousRevenue = (float) Order::query()
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('payment_status', 'paid')
            ->sum('total');

        $periodOrdersCount = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $dailyRevenue = collect(range(0, $days))->map(function (int $offset) use ($start) {
            $date = $start->copy()->addDays($offset);
            $from = $date->copy()->startOfDay();
            $to = $date->copy()->endOfDay();

            $revenue = (float) Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('payment_status', 'paid')
                ->sum('total');

            $orders = Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->count();

            return [
                'date' => $date->format('d M'),
                'revenue' => $revenue,
                'orders' => $orders,
            ];
        })->values();

        $statusData = Order::query()
            ->selectRaw('status, COUNT(*) as value')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['name' => ucfirst((string) $row->status), 'value' => (int) $row->value])
            ->values();

        $paymentData = Order::query()
            ->selectRaw('payment_method, COUNT(*) as value')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'name' => match ($row->payment_method) {
                    'tmoney' => 'Tmoney',
                    'flooz' => 'Flooz',
                    'cash_on_delivery' => 'Livraison',
                    default => ucfirst((string) $row->payment_method),
                },
                'value' => (int) $row->value,
            ])
            ->values();

        $previousOrdersCount = Order::query()
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->count();

        return response()->json([
            // Global KPIs (as before)
            'total_revenue' => (float) Order::where('payment_status', 'paid')->sum('total'),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_customers' => Profile::count(),

            // Period KPIs
            'period_revenue' => $totalRevenue,
            'period_orders' => $periodOrdersCount,
            'new_customers' => Profile::whereBetween('created_at', [$start, $end])->count(),
            'avg_order' => $periodOrdersCount > 0 ? round($totalRevenue / $periodOrdersCount, 2) : 0,
            'revenue_change' => $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'orders_change' => $previousOrdersCount > 0 ? round((($periodOrdersCount - $previousOrdersCount) / $previousOrdersCount) * 100, 1) : 0,

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
