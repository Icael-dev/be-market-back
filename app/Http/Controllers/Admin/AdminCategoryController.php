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
        return response()->json(Category::with('specs')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories,slug',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'specs' => 'sometimes|nullable|array',
            'specs.*.key' => 'required_with:specs|string',
            'specs.*.placeholder' => 'sometimes|nullable|string',
        ]);

        $category = Category::create(collect($validated)->except('specs')->toArray());

        if (isset($validated['specs'])) {
            foreach ($validated['specs'] as $spec) {
                $category->specs()->create([
                    'key' => $spec['key'],
                    'placeholder' => $spec['placeholder'] ?? null,
                ]);
            }
        }

        return response()->json($category->load('specs'), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Category::with('specs')->findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:categories,slug,' . $id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'specs' => 'sometimes|nullable|array',
            'specs.*.key' => 'required_with:specs|string',
            'specs.*.placeholder' => 'sometimes|nullable|string',
        ]);

        $category = Category::findOrFail($id);
        $category->update(collect($validated)->except('specs')->toArray());

        if (array_key_exists('specs', $validated)) {
            $category->specs()->delete();
            if (isset($validated['specs'])) {
                foreach ($validated['specs'] as $spec) {
                    $category->specs()->create([
                        'key' => $spec['key'],
                        'placeholder' => $spec['placeholder'] ?? null,
                    ]);
                }
            }
        }

        return response()->json($category->load('specs'));
    }

    public function destroy(string $id): JsonResponse
    {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
