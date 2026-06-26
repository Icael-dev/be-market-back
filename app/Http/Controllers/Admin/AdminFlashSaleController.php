<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFlashSaleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FlashSale::orderBy('starts_at', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $flashSale = FlashSale::create($request->validate([
            'title' => 'required|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'product_ids' => 'sometimes|array',
            'product_ids.*' => 'uuid|exists:products,id',
            'discount_percentage' => 'sometimes|nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($flashSale, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(FlashSale::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);
        $flashSale->update($request->validate([
            'title' => 'sometimes|string',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'product_ids' => 'sometimes|array',
            'product_ids.*' => 'uuid|exists:products,id',
            'discount_percentage' => 'sometimes|nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($flashSale);
    }

    public function destroy(string $id): JsonResponse
    {
        FlashSale::findOrFail($id)->delete();
        return response()->json(['message' => 'Flash sale deleted.']);
    }
}
