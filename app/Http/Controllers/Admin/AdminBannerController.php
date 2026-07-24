<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBannerController extends Controller
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
        return response()->json(Banner::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $banner = Banner::create($request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:255',
            'image_url' => ['required', fn ($attribute, $value, $fail) => $this->validateImageValue($value, $fail)],
            'link_url' => 'sometimes|nullable|string|max:255',
            'button_text' => 'sometimes|nullable|string|max:255',
            'sort_order' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($banner, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Banner::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->update($request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:255',
            'image_url' => ['sometimes', fn ($attribute, $value, $fail) => $this->validateImageValue($value, $fail)],
            'link_url' => 'sometimes|nullable|string|max:255',
            'button_text' => 'sometimes|nullable|string|max:255',
            'sort_order' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($banner);
    }

    public function destroy(string $id): JsonResponse
    {
        Banner::findOrFail($id)->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }
}
