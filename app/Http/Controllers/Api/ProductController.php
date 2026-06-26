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
        $query = Product::where('is_active', true)->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        } elseif ($request->filled('category')) {
            $category = (string) $request->query('category');

            // `category` peut être soit un UUID/id de category, soit un slug.
            // On tente une détection robuste:
            // - UUID v4/v1 (général) => on filtre sur category_id
            // - sinon => on filtre sur category.slug
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
            $term = '%'.$request->query('search').'%';
            $query->where(fn ($builder) => $builder
                ->where('name', 'ilike', $term)
                ->orWhere('description', 'ilike', $term)
                ->orWhere('sku', 'ilike', $term));
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

        if ($request->filled('page')) {
            return response()->json($query->paginate((int) $request->query('per_page', 20)));
        }

        return response()->json($query->get());
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($product);
    }
}
