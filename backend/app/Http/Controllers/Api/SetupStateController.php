<?php

namespace App\Http\Controllers\Api;

use App\Enums\SetupStep;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Setup\AppSetupStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetupStateController extends Controller
{
    public function __construct(
        protected AppSetupStateMachine $stateMachine,
    ) {}

    /**
     * Get current setup state and build allowed status for the app.
     */
    public function show(App $app): JsonResponse
    {
        return response()->json([
            'data' => $this->stateMachine->getState($app),
        ]);
    }

    /**
     * Transition or rollback setup step, invalidating downstream steps if going back.
     */
    public function update(Request $request, App $app): JsonResponse
    {
        $validated = $request->validate([
            'step' => ['nullable', 'string', 'in:'.implode(',', array_column(SetupStep::cases(), 'value'))],
            'platform' => ['nullable', 'string', 'in:woo,shopify'],
        ]);

        if (isset($validated['platform']) && $validated['platform'] !== $app->platform) {
            $this->stateMachine->changePlatform($app, $validated['platform']);
        } elseif (isset($validated['step'])) {
            $targetStep = SetupStep::from($validated['step']);
            $currentStep = SetupStep::fromString($app->setup_step);

            if ($targetStep->order() < $currentStep->order()) {
                $this->stateMachine->rollbackTo($app, $targetStep);
            } else {
                $this->stateMachine->advanceTo($app, $targetStep);
            }
        }

        return response()->json([
            'message' => 'Setup state updated successfully.',
            'data' => $this->stateMachine->getState($app->fresh() ?? $app),
        ]);
    }
}
