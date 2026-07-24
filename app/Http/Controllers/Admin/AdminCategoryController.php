<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    private function validateImageValue($value, $fail): void
    {
        if (! is_string($value)) {
            $fail('The image must be a valid URL or path.');

            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return;
        }

        if (str_starts_with($value, '/')) {
            return;
        }

        $fail('The image must be a valid URL or path.');
    }

    public function index(): JsonResponse
    {
        return response()->json(Category::withTrashed()->with('specs')->orderBy('name')->get());
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
            'image_url' => ['sometimes', fn ($attribute, $value, $fail) => $this->validateImageValue($value, $fail)],
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
            'slug' => 'sometimes|string|unique:categories,slug,'.$id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'specs' => 'sometimes|nullable|array',
            'specs.*.key' => 'required_with:specs|string',
            'specs.*.placeholder' => 'sometimes|nullable|string',
            'image_url' => ['sometimes', fn ($attribute, $value, $fail) => $this->validateImageValue($value, $fail)],
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
        Category::withTrashed()->findOrFail($id)->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    public function restore(string $id): JsonResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        return response()->json(['message' => 'Category restored.', 'category' => $category->load('specs')]);
    }
}
