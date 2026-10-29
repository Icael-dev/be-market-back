<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    private const ALLOWED_FOLDERS = ['products', 'banners', 'blog'];

    public function show(string $folder, string $path, Request $request): StreamedResponse
    {
        if (! in_array($folder, self::ALLOWED_FOLDERS, true)) {
            abort(404);
        }

        if ($this->isPathTraversal($path)) {
            abort(404);
        }

        $fullPath = "{$folder}/{$path}";

        if (! Storage::disk('public')->exists($fullPath)) {
            abort(404);
        }

        $mime = Storage::disk('public')->mimeType($fullPath);
        $stream = Storage::disk('public')->readStream($fullPath);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function isPathTraversal(string $path): bool
    {
        if ($path === '' || $path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        if (str_contains($path, '\\')) {
            return true;
        }

        if (str_contains($path, '..')) {
            return true;
        }

        if (str_contains($path, "\0")) {
            return true;
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                return true;
            }
        }

        return false;
    }
}
