<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class AuthenticateApi extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (empty($guards)) {
            $guards = ['api'];
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }

    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            null
        );
    }
}
