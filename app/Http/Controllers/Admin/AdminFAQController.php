<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminFAQController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            FAQ::orderByRaw('sort_order ASC, created_at DESC')->get()
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(FAQ::findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = FAQ::create($validator->validated());

        return response()->json($faq, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $faq = FAQ::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'question' => ['sometimes', 'string', 'max:255'],
            'answer' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq->update($validator->validated());

        return response()->json($faq);
    }

    public function destroy(string $id): JsonResponse
    {
        $faq = FAQ::findOrFail($id);
        $faq->delete();

        return response()->json(['message' => 'FAQ supprimee.']);
    }
}
