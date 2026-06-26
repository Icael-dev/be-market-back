<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function points(Request $request): JsonResponse
    {
        $loyaltyPoints = LoyaltyPoints::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'points' => 0,
                'total_earned' => 0,
                'total_redeemed' => 0,
            ]
        );

        return response()->json($loyaltyPoints);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = LoyaltyTransaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }
}
