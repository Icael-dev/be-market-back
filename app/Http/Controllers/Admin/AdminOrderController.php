<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('user', 'items.product')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->get());
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $order = Order::with('user', 'items.product')->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,processing,paid,failed,refunded',
        ]);

        $order->update($data);

        return response()->json($order->refresh()->load('user', 'items.product'));
    }
}
