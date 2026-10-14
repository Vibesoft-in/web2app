<?php

use App\Http\Middleware\EnsureAppTokenScope;
use App\Http\Middleware\EnsureStepAllowed;
use App\Http\Middleware\ResolveAppKey;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::prefix('v1')
                ->group(base_path('routes/v1.php'));
            Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'step' => EnsureStepAllowed::class,
            'resolve.app_key' => ResolveAppKey::class,
            'token.scope' => EnsureAppTokenScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('sentry') && config('sentry.dsn')) {
                app('sentry')->captureException($e);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Resource not found.',
                    ],
                ], 404);
            }
        });
    })->create();
