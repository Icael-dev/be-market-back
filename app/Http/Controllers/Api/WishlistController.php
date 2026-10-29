<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)->with('product')->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->only(['product_id']);
        $validator = Validator::make($data, [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::find($data['product_id']);
        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $item = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json($item, 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $item = Wishlist::where('user_id', $request->user()->id)->where('id', $id)->first();
        if (! $item) {
            return response()->json(['message' => 'Wishlist item not found.'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Wishlist item removed.']);
    }
}
