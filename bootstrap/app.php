<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AdminSuperAdminMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

if (function_exists('ini_set')) {
    ini_set('upload_max_filesize', '12M');
    ini_set('post_max_size', '14M');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->remove(HandleCors::class);

        $middleware->append([
            CorsMiddleware::class,
            SecurityHeaders::class,
        ]);

        $middleware->appendToGroup('api', [
            EnsureFrontendRequestsAreStateful::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'admin.super' => AdminSuperAdminMiddleware::class,
            'auth' => Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
