<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('');
            $response->setStatusCode(204);
        } else {
            $response = $next($request);
        }

        $allowedOrigins = $this->allowedOrigins();
        $origin = $request->headers->get('Origin');
        $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : '';

        if ($allowOrigin !== '') {
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-XSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    /**
     * @return string[]
     */
    protected function allowedOrigins(): array
    {
        $configured = env('CORS_ALLOWED_ORIGINS');

        if ($configured) {
            return array_filter(array_map('trim', explode(',', $configured)));
        }

        return [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5174',
        ];
    }
}
