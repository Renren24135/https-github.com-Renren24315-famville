<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureTwoFactorChallenged;
use App\Http\Middleware\ForceHttps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if ($trustedProxies = env('APP_TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $middleware->prependToGroup('web', [ForceHttps::class]);
        $middleware->appendToGroup('web', [ContentSecurityPolicy::class, EnsureAccountIsActive::class, EnsureTwoFactorChallenged::class]);

        // PayMongo calls this directly — it has no Laravel session and
        // sends no CSRF token. The route is instead protected by verifying
        // PayMongo's own HMAC signature on every request (see
        // PayMongoService::verifyWebhookSignature()).
        $middleware->validateCsrfTokens(except: ['payments/paymongo/webhook']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
