<?php

namespace App\Http\Middleware;

use App\Models\App;
use App\Support\AppContext;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAppKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-App-Key');

        if (empty($appKey)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_APP_KEY',
                    'message' => 'Missing required X-App-Key header.',
                ],
            ], 401);
        }

        /** @var App|null $app */
        $app = App::withoutGlobalScopes()
            ->with('tenant')
            ->where('app_key', $appKey)
            ->first();

        if (! $app) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_APP_KEY',
                    'message' => 'Invalid X-App-Key header.',
                ],
            ], 401);
        }

        $tenant = $app->tenant;
        if (! $tenant || $tenant->status === 'suspended') {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_INACTIVE',
                    'message' => 'Tenant account is inactive or suspended.',
                ],
            ], 403);
        }

        TenantContext::set($tenant);
        AppContext::set($app);
        $request->attributes->set('app', $app);

        return $next($request);
    }

    /**
     * Clean up context after request termination.
     */
    public function terminate(Request $request, Response $response): void
    {
        AppContext::forget();
        TenantContext::forget();
    }
}
