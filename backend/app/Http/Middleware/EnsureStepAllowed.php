<?php

namespace App\Http\Middleware;

use App\Enums\SetupStep;
use App\Models\App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStepAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredStepName): Response
    {
        $appParam = $request->route('app');

        $app = null;
        if ($appParam instanceof App) {
            $app = $appParam;
        } elseif (is_string($appParam)) {
            $app = App::where('ulid', $appParam)->first();
        }

        if (! $app) {
            return $next($request);
        }

        $currentStep = SetupStep::fromString($app->setup_step);
        $requiredStep = SetupStep::tryFrom($requiredStepName);

        if ($requiredStep === null) {
            return $next($request);
        }

        // Rule: route step order <= current step order + 1
        if (! $requiredStep->isAllowedFrom($currentStep)) {
            return response()->json([
                'error' => [
                    'code' => 'STEP_LOCKED',
                    'message' => "Step '{$requiredStep->value}' is locked. Complete previous setup steps first.",
                    'current_step' => $currentStep->value,
                    'required_step' => $requiredStep->value,
                ],
            ], 403);
        }

        return $next($request);
    }
}
