<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $category = Category::create($request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories,slug',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($category, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Category::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->validate([
            'name' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:categories,slug,' . $id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($category);
    }

    public function destroy(string $id): JsonResponse
    {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
