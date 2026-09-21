<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('is_active', true)->with(['category', 'activeFlashSale']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        } elseif ($request->filled('category')) {
            $category = (string) $request->query('category');

            $isUuid = preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $category) === 1;

            if ($isUuid) {
                $query->where('category_id', $category);
            } else {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
            }
        }

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', $request->query('exclude_id'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('search')) {
            $term = $request->query('search');
            if (mb_strlen($term) > 100) {
                $term = mb_substr($term, 0, 100);
            }
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
            $like = '%'.$escaped.'%';
            $query->where(fn ($builder) => $builder
                ->where('name', 'ilike', $like)
                ->orWhere('description', 'ilike', $like)
                ->orWhere('sku', 'ilike', $like));
        }

        if ($request->filled('brand')) {
            $query->whereRaw("specs->>'brand' = ?", [$request->query('brand')]);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        if ($request->boolean('flash_sale')) {
            $query->whereHas('activeFlashSale');
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->query('condition'));
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        if ($request->boolean('on_sale')) {
            $query->whereNotNull('compare_price')->where('compare_price', '>', 'price');
        }

        $sort = match ($request->query('sort')) {
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'newest' => ['created_at', 'desc'],
            'name_asc' => ['name', 'asc'],
            'name_desc' => ['name', 'desc'],
            default => ['created_at', 'desc'],
        };
        $query->orderBy($sort[0], $sort[1]);

        if ($request->filled('limit')) {
            return response()->json($query->limit((int) $request->query('limit'))->get());
        }

        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);

        if ($request->filled('page')) {
            return response()->json($query->paginate($perPage));
        }

        $products = $query->get();

        return response()->json([
            'data' => $products,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $products->count(),
            'total' => $products->count(),
        ]);
    }

    public function priceBounds(Request $request): JsonResponse
    {
        $query = Product::where('is_active', true);

        if ($request->filled('category')) {
            $category = (string) $request->query('category');
            $isUuid = preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $category) === 1;

            if ($isUuid) {
                $query->where('category_id', $category);
            } else {
                $query->whereHas('category', fn ($q) => $q->where('slug', $category));
            }
        }

        if ($request->filled('search')) {
            $term = '%'.$request->query('search').'%';
            $query->where(fn ($builder) => $builder
                ->where('name', 'ilike', $term)
                ->orWhere('description', 'ilike', $term));
        }

        if ($request->filled('brand')) {
            $query->whereRaw("specs->>'brand' = ?", [$request->query('brand')]);
        }

        $minMax = $query->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        $min = $minMax->min_price !== null ? (float) $minMax->min_price : 0;
        $max = $minMax->max_price !== null ? (float) $minMax->max_price : 0;

        return response()->json([
            'min' => $min,
            'max' => $max,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'activeFlashSale'])
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($product);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $limit = min((int) $request->query('limit', 10), 20);

        $products = Product::query()
            ->where('is_active', true)
            ->with('activeFlashSale')
            ->select(['id', 'name', 'slug', 'sku', 'price', 'images'])
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'ilike', "%{$query}%")
                    ->orWhere('sku', 'ilike', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return response()->json($products);
    }
}
