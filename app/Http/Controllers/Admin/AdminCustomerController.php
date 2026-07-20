<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Profile::with('user.orders')->orderBy('created_at', 'desc');

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->input('email').'%');
        }

        $customers = $query->get()->map(function (Profile $profile) {
            $orders = $profile->user?->orders ?? collect();
            $data = $profile->toArray();
            $data['user_id'] = $profile->user_id;
            $data['orders_count'] = $orders->count();
            $data['total_spent'] = (float) $orders->sum('total');
            unset($data['user']);

            return $data;
        });

        return response()->json($customers);
    }

    public function show(string $id): JsonResponse
    {
        $profile = Profile::with('user')->where('user_id', $id)->firstOrFail();

        return response()->json($profile);
    }

    public function orders(string $id): JsonResponse
    {
        $profile = Profile::with(['user.orders.items.product', 'user.orders.payments'])->where('user_id', $id)->firstOrFail();

        return response()->json($profile->user?->orders ?? []);
    }

    public function loyalty(string $id): JsonResponse
    {
        $points = LoyaltyPoints::where('user_id', $id)->first();
        $transactions = LoyaltyTransaction::where('user_id', $id)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'points' => $points,
            'transactions' => $transactions,
        ]);
    }

    public function payments(string $id): JsonResponse
    {
        $payments = Payment::where('user_id', $id)
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }
}
