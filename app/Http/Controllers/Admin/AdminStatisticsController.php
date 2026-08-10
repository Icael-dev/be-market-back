<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Profile;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStatisticsController extends Controller
{
    public function __construct(private ExportService $exports) {}

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

        $dailyRevenueStats = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->date => (float) $row->revenue]);

        $dailyOrderStats = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->date => (int) $row->orders]);

        $dailyRevenue = collect(range(0, $days))->map(function (int $offset) use ($start, $dailyRevenueStats, $dailyOrderStats) {
            $date = $start->copy()->addDays($offset);
            $dateKey = $date->format('Y-m-d');

            return [
                'date' => $date->format('d M'),
                'revenue' => $dailyRevenueStats->get($dateKey, 0),
                'orders' => $dailyOrderStats->get($dateKey, 0),
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

    public function export(Request $request): StreamedResponse
    {
        return $this->exports->csv($request->query('type', 'orders'));
    }

    public function topProducts(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->query('days', 30), 365));
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select('order_items.product_id', 'order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.total) as total_revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => (float) $row->total_revenue,
            ])
            ->values();

        return response()->json($topProducts);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $threshold = max(1, (int) $request->query('threshold', 10));

        $products = Product::query()
            ->where('stock', '<=', $threshold)
            ->where('is_active', true)
            ->orderBy('stock')
            ->get(['id', 'name', 'sku', 'stock', 'price']);

        return response()->json($products);
    }

    public function couponStats(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->query('days', 30), 365));
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $couponsUsed = CouponUsage::query()
            ->join('coupons', 'coupon_usages.coupon_id', '=', 'coupons.id')
            ->whereBetween('coupon_usages.created_at', [$start, $end])
            ->select('coupons.code', 'coupons.discount_type', 'coupons.discount_value')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('coupons.id', 'coupons.code', 'coupons.discount_type', 'coupons.discount_value')
            ->orderByDesc('usage_count')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'discount_type' => $row->discount_type,
                'discount_value' => (float) $row->discount_value,
                'usage_count' => (int) $row->usage_count,
            ])
            ->values();

        return response()->json([
            'coupons_used' => $couponsUsed,
            'total_coupon_usages' => $couponsUsed->sum('usage_count'),
        ]);
    }

    public function categorySales(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->query('days', 30), 365));
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $categorySales = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select('categories.id', 'categories.name')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.total) as total_revenue')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [
                'category_id' => $row->id,
                'category_name' => $row->name,
                'orders_count' => (int) $row->orders_count,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => (float) $row->total_revenue,
            ])
            ->values();

        return response()->json($categorySales);
    }

    public function newCustomers(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 5), 20));

        $customers = Profile::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'full_name', 'email', 'phone', 'created_at']);

        return response()->json($customers);
    }

    public function topCustomers(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 5), 20));

        $customers = Order::query()
            ->where('payment_status', 'paid')
            ->select('user_id')
            ->selectRaw('SUM(total) as total_spent')
            ->selectRaw('COUNT(*) as orders_count')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $profile = Profile::find($row->user_id);

                return [
                    'user_id' => $row->user_id,
                    'name' => $profile?->full_name ?? '—',
                    'email' => $profile?->email ?? '—',
                    'total_spent' => (float) $row->total_spent,
                    'orders_count' => (int) $row->orders_count,
                ];
            })
            ->values();

        return response()->json($customers);
    }

    public function unsoldProducts(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 5), 50));

        $soldProductIds = OrderItem::select('product_id')->distinct()->pluck('product_id');

        $products = Product::query()
            ->whereNotIn('id', $soldProductIds)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'stock', 'price']);

        return response()->json($products);
    }

    public function failedPayments(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 5), 50));

        $orders = Order::query()
            ->whereIn('payment_status', ['failed', 'cancelled', 'refunded'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'order_number', 'status', 'payment_status', 'payment_method', 'total', 'created_at']);

        return response()->json($orders);
    }

    public function deliveryStats(Request $request): JsonResponse
    {
        $start = now()->subDays(30)->startOfDay();
        $end = now()->endOfDay();

        $statusStats = Order::query()
            ->selectRaw('status, COUNT(*) as value')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['name' => ucfirst((string) $row->status), 'value' => (int) $row->value])
            ->values();

        $avgShippingFee = (float) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->avg('shipping_fee');

        return response()->json([
            'status_stats' => $statusStats,
            'avg_shipping_fee' => round($avgShippingFee, 2),
        ]);
    }
}
