<?php

namespace App\Http\Middleware;

use App\Models\App;
use App\Models\AppUser;
use App\Support\AppContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppTokenScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof AppUser) {
            /** @var App|null $app */
            $app = $request->attributes->get('app') ?? AppContext::get();

            if (! $app) {
                return response()->json([
                    'error' => [
                        'code' => 'INVALID_APP_KEY',
                        'message' => 'Application context missing.',
                    ],
                ], 401);
            }

            // Verify token is scoped specifically to this app_id
            if (! $user->tokenCan("app:{$app->id}")) {
                return response()->json([
                    'error' => [
                        'code' => 'APP_MISMATCH',
                        'message' => 'Token is not authorized for this application.',
                    ],
                ], 403);
            }

            // Verify user belongs to this app
            if ($user->app_id !== $app->id) {
                return response()->json([
                    'error' => [
                        'code' => 'APP_MISMATCH',
                        'message' => 'User does not belong to this application.',
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
