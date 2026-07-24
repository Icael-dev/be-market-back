<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user || ! $user->isStaffMember()) {
            return new JsonResponse([
                'message' => 'Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
