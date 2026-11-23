<?php

use App\Modules\Identity\Http\Middleware\CheckPermission;
use App\Modules\Identity\Http\Middleware\EnsureActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perm' => CheckPermission::class,
            'active' => EnsureActive::class,
        ]);

        // Public customer API endpoints are stateless (no CSRF).
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();