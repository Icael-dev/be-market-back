<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminLegalPageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            LegalPage::orderBy('created_at', 'desc')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:legal_pages,slug'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $page = LegalPage::create($data);

        return response()->json($page, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $page = LegalPage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:legal_pages,slug,'.$id],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page->update($validator->validated());

        return response()->json($page);
    }

    public function destroy(string $id): JsonResponse
    {
        $page = LegalPage::findOrFail($id);
        $page->delete();

        return response()->json(['message' => 'Page juridique supprimee.']);
    }
}
