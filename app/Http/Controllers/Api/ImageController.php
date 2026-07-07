<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    public function show(string $path, Request $request): StreamedResponse
    {
        $fullPath = "products/{$path}";

        if (!Storage::disk('public')->exists($fullPath)) {
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
}
