<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return new JsonResponse([
                'message' => 'Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
