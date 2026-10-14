<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $tenant = $user->tenant;

            if (! $tenant || $tenant->status === 'suspended') {
                return response()->json([
                    'error' => [
                        'code' => 'TENANT_INACTIVE',
                        'message' => 'Your tenant account is not active.',
                    ],
                ], 403);
            }

            TenantContext::set($tenant);
        }

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        TenantContext::forget();
    }
}
