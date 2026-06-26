<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            $data['orders_count'] = $orders->count();
            $data['total_spent'] = (float) $orders->sum('total');
            unset($data['user']);

            return $data;
        });

        return response()->json($customers);
    }
}
