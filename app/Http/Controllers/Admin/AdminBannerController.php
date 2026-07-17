<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Banner::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $banner = Banner::create($request->validate([
            'title' => 'required|string',
            'subtitle' => 'sometimes|nullable|string',
            'image_url' => 'required|string',
            'link_url' => 'sometimes|nullable|string',
            'button_text' => 'sometimes|nullable|string',
            'position' => 'sometimes|string',
            'sort_order' => 'sometimes|integer',
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
            'title' => 'sometimes|string',
            'subtitle' => 'sometimes|nullable|string',
            'image_url' => 'sometimes|string',
            'link_url' => 'sometimes|nullable|string',
            'button_text' => 'sometimes|nullable|string',
            'position' => 'sometimes|string',
            'sort_order' => 'sometimes|integer',
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
