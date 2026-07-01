<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::with('category')->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock_alert' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['nullable', 'url'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['string'],
            'dimensions' => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric'],
            'dimensions.width' => ['nullable', 'numeric'],
            'dimensions.height' => ['nullable', 'numeric'],
            'variants' => ['nullable', 'array'],
            'variants.sizes' => ['nullable', 'array'],
            'variants.colors' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (empty($data['sku'])) {
            $data['sku'] = 'PROD-' . Str::random(8);
        }

        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'min_stock_alert' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['nullable', 'url'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['string'],
            'dimensions' => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric'],
            'dimensions.width' => ['nullable', 'numeric'],
            'dimensions.height' => ['nullable', 'numeric'],
            'variants' => ['nullable', 'array'],
            'variants.sizes' => ['nullable', 'array'],
            'variants.colors' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['slug']) && $data['slug'] !== $product->slug) {
            $data['slug'] = Str::slug($data['slug']);
        } else {
            unset($data['slug']);
        }

        if (isset($data['sku']) && $data['sku'] === '') {
            $data['sku'] = null;
        }

        if (empty($data['sku']) && !$product->sku) {
            $data['sku'] = 'PROD-' . Str::random(8);
        }

        if (isset($data['compare_price']) && $data['compare_price'] === '') {
            $data['compare_price'] = null;
        }

        if (isset($data['weight']) && $data['weight'] === '') {
            $data['weight'] = null;
        }

        $product->update($data);
        return response()->json($product->load('category'));
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('image')->store('products', 'public');

        $filename = basename($path);

        return response()->json([
            'url' => url("/api/images/{$filename}"),
        ], 201);
    }
}
