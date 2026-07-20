<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    private function getCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        return response()->json(
            $cart->items()->with('product.activeFlashSale')->get()
        );
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->only(['product_id', 'quantity']);
        $validator = Validator::make($data, [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::find($data['product_id']);
        if (! $product || ! $product->is_active) {
            return response()->json(['message' => 'Product unavailable.'], 400);
        }

        if ($product->stock < $data['quantity']) {
            return response()->json(['message' => 'Insufficient stock.'], 400);
        }

        $cart = $this->getCart($request);
        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->quantity += $data['quantity'];
            $item->save();
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
            ]);
        }

        return response()->json($item->load('product'), 201);
    }

    public function updateItem(Request $request, string $id): JsonResponse
    {
        $data = $request->only(['quantity']);
        $validator = Validator::make($data, [
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = $this->getCart($request);
        $item = $cart->items()->where('id', $id)->first();

        if (! $item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $product = $item->product;
        if (! $product || $product->stock < $data['quantity']) {
            return response()->json(['message' => 'Insufficient stock.'], 400);
        }

        $item->quantity = $data['quantity'];
        $item->save();

        return response()->json($item->load('product'));
    }

    public function removeItem(Request $request, string $id): JsonResponse
    {
        $cart = $this->getCart($request);
        $item = $cart->items()->where('id', $id)->first();

        if (! $item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $item->delete();

        return response()->json(null, 204);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();

        return response()->json(null, 204);
    }
}
