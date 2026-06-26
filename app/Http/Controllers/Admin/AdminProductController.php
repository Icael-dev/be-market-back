<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::with('category')->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['required', 'string', 'unique:products,slug'],
            'sku' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['required', 'numeric'],
            'compare_price' => ['sometimes', 'nullable', 'numeric'],
            'stock' => ['required', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'images' => ['sometimes', 'array'],
            'specs' => ['sometimes', 'array'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'nullable', 'numeric'],
        ]);

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }
        return response()->json($product);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'slug' => ['sometimes', 'string', 'unique:products,slug,' . $product->id],
            'sku' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric'],
            'compare_price' => ['sometimes', 'nullable', 'numeric'],
            'stock' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'images' => ['sometimes', 'array'],
            'specs' => ['sometimes', 'array'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'nullable', 'numeric'],
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }
}
