<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind tunnels (ngrok) and reverse proxies: respect
        // X-Forwarded-* headers so https URLs are generated correctly.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'not_banned' => EnsureNotBanned::class,
            'super_admin' => EnsureSuperAdmin::class,
        ]);

        $middleware->appendToGroup('web', EnsureNotBanned::class);

        // Gateway webhooks are signature-verified, not CSRF-protected.
        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
