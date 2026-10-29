<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLoyaltyController extends Controller
{
    public function transactions(Request $request): JsonResponse
    {
        $query = LoyaltyTransaction::with(['user', 'order'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        return response()->json($query->paginate((int) $request->query('per_page', 10)));
    }

    public function points(): JsonResponse
    {
        return response()->json(LoyaltyPoints::with('user')->orderBy('points', 'desc')->paginate((int) request()->query('per_page', 10)));
    }
}
