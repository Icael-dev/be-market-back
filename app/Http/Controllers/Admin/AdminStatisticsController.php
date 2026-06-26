<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalSales = Order::where('status', 'completed')->sum('total_amount');
        $orderCount = Order::count();
        $customerCount = DB::table('users')->count();

        return response()->json([
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'customer_count' => $customerCount,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $orders = Order::when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->get();

        return response()->json($orders);
    }
}
