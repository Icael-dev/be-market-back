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
        return response()->json(FlashSale::with('product')->orderBy('starts_at', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $flashSale = FlashSale::create($request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'discount_percentage' => 'required|integer|min:1|max:99',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($flashSale->load('product'), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(FlashSale::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);
        $flashSale->update($request->validate([
            'product_id' => 'sometimes|uuid|exists:products,id',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'discount_percentage' => 'sometimes|integer|min:1|max:99',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($flashSale->load('product'));
    }

    public function destroy(string $id): JsonResponse
    {
        FlashSale::findOrFail($id)->delete();

        return response()->json(['message' => 'Flash sale deleted.']);
    }
}
