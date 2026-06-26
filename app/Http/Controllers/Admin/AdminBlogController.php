<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BlogPost::orderBy('published_at', 'desc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $blogPost = BlogPost::create($request->validate([
            'title' => 'required|string',
            'slug' => 'required|string|unique:blog_posts,slug',
            'excerpt' => 'sometimes|nullable|string',
            'content' => 'required|string',
            'image_url' => 'sometimes|nullable|string',
            'published_at' => 'sometimes|nullable|date',
            'is_published' => 'sometimes|boolean',
        ]));

        return response()->json($blogPost, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(BlogPost::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $blogPost = BlogPost::findOrFail($id);
        $blogPost->update($request->validate([
            'title' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:blog_posts,slug,' . $id,
            'excerpt' => 'sometimes|nullable|string',
            'content' => 'sometimes|string',
            'image_url' => 'sometimes|nullable|string',
            'published_at' => 'sometimes|nullable|date',
            'is_published' => 'sometimes|boolean',
        ]));

        return response()->json($blogPost);
    }

    public function destroy(string $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();
        return response()->json(['message' => 'Blog post deleted.']);
    }
}
