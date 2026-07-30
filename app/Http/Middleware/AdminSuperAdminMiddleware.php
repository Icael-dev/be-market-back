<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_ADMIN) {
            return new JsonResponse([
                'message' => 'Super admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
