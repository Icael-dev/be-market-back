<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $categories = Cache::remember('categories.active', 3600, function () {
                return Category::where('is_active', true)
                    ->with('specs')
                    ->orderBy('sort_order')
                    ->get();
            });
        } catch (\Throwable $e) {
            $categories = Category::where('is_active', true)
                ->with('specs')
                ->orderBy('sort_order')
                ->get();
        }

        if (! $categories instanceof \Illuminate\Support\Collection) {
            $categories = Category::where('is_active', true)
                ->with('specs')
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json(CategoryResource::collection($categories));
    }
}
