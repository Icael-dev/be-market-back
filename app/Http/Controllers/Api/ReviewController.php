<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with('user.profile');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(ReviewRequest $request): JsonResponse
    {
        $data = $request->only(['product_id', 'rating', 'title', 'comment']);

        $product = Product::find($data['product_id']);
        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $verifiedPurchase = Order::where('user_id', $request->user()->id)
            ->where('payment_status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => $verifiedPurchase,
        ]);

        return response()->json($review->load('user.profile'), 201);
    }
}
