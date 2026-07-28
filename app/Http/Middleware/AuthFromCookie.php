<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthFromCookie
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('be_market_token');

        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
