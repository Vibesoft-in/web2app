<?php

namespace App\Services\Setup;

use App\Enums\SetupStep;
use App\Models\App;
use Illuminate\Support\Facades\DB;

class AppSetupStateMachine
{
    /**
     * Compute whether build is allowed for the app.
     * Rule: connection verified + config published + subscription active.
     */
    public function isBuildAllowed(App $app): bool
    {
        return empty($this->getBuildBlockingReasons($app));
    }

    /**
     * Get specific list of reasons preventing build creation.
     *
     * @return list<string>
     */
    public function getBuildBlockingReasons(App $app): array
    {
        $reasons = [];

        // 1. Connection must be verified
        $connection = $app->connection;
        if (! $connection || $connection->status !== 'verified') {
            $reasons[] = 'Store connection must be verified.';
        }

        // 2. Config must be published
        $hasPublishedConfig = $app->configs()
            ->where('state', 'published')
            ->exists();

        if (! $hasPublishedConfig) {
            $reasons[] = 'App configuration must be published.';
        }

        // 3. Subscription must be active and have build entitlement
        $tenant = $app->tenant;
        if (! $tenant || ! $tenant->hasActiveSubscription($app->id)) {
            $reasons[] = 'An active subscription plan is required.';
        } elseif (! app(\App\Services\Billing\EntitlementService::class)->can($tenant, 'build', $app)) {
            if (app(\App\Services\Billing\EntitlementService::class)->isFeatureLocked($tenant, $app)) {
                $reasons[] = 'Subscription expired and grace period ended.';
            } else {
                $reasons[] = 'Monthly build limit reached on your current plan.';
            }
        }

        return $reasons;
    }

    /**
     * Get comprehensive setup state diagnostics for the app.
     *
     * @return array<string, mixed>
     */
    public function getState(App $app): array
    {
        $currentStep = SetupStep::fromString($app->setup_step);
        $order = $currentStep->order();

        $allSteps = SetupStep::cases();
        $completedSteps = [];
        $unlockedSteps = [];

        foreach ($allSteps as $step) {
            if ($step->order() < $order) {
                $completedSteps[] = $step->value;
            }
            if ($step->isAllowedFrom($currentStep)) {
                $unlockedSteps[] = $step->value;
            }
        }

        $connection = $app->connection;
        $publishedConfig = $app->configs()->where('state', 'published')->latest('version')->first();
        $draftConfig = $app->configs()->where('state', 'draft')->latest('version')->first();
        $tenant = $app->tenant;
        $subscriptionActive = $tenant ? $tenant->hasActiveSubscription($app->id) : false;

        $blockingReasons = $this->getBuildBlockingReasons($app);

        return [
            'app' => [
                'ulid' => $app->ulid,
                'name' => $app->name,
                'platform' => $app->platform,
                'package_name' => $app->package_name,
                'setup_step' => $app->setup_step,
                'status' => $app->status,
            ],
            'current_step' => $currentStep->value,
            'step_order' => $order,
            'completed_steps' => $completedSteps,
            'unlocked_steps' => $unlockedSteps,
            'connection' => $connection ? [
                'status' => $connection->status,
                'site_url' => $connection->site_url,
                'platform' => $connection->platform,
                'last_verified_at' => $connection->last_verified_at?->toIso8601String(),
            ] : null,
            'config' => [
                'has_draft' => $draftConfig !== null,
                'has_published' => $publishedConfig !== null,
                'latest_draft_version' => $draftConfig?->version,
                'latest_published_version' => $publishedConfig?->version,
            ],
            'subscription' => [
                'active' => $subscriptionActive,
            ],
            'build_allowed' => empty($blockingReasons),
            'build_blocking_reasons' => $blockingReasons,
        ];
    }

    /**
     * Advance setup step to target step.
     */
    public function advanceTo(App $app, SetupStep $targetStep): void
    {
        $currentStep = SetupStep::fromString($app->setup_step);

        if ($targetStep->order() > $currentStep->order()) {
            $app->setup_step = $targetStep->value;
            $app->save();
        }
    }

    /**
     * Rollback setup step to an earlier step, invalidating downstream artifacts.
     */
    public function rollbackTo(App $app, SetupStep $targetStep): void
    {
        DB::transaction(function () use ($app, $targetStep) {
            // 1. If rolling back to platform (or changing platform)
            if ($targetStep === SetupStep::PLATFORM) {
                if ($app->connection) {
                    $app->connection->update([
                        'status' => 'not_started',
                        'credentials' => null,
                        'last_verified_at' => null,
                        'last_error_code' => null,
                    ]);
                }
                $app->status = 'draft';
            }

            // 2. If rolling back to connect or earlier, unpublish published configs
            if ($targetStep->order() <= SetupStep::CONNECT->order()) {
                $app->configs()->where('state', 'published')->update([
                    'state' => 'draft',
                ]);
            }

            $app->setup_step = $targetStep->value;
            $app->save();
        });
    }

    /**
     * Change app platform and reset connection/configs accordingly.
     */
    public function changePlatform(App $app, string $platform): void
    {
        DB::transaction(function () use ($app, $platform) {
            $app->platform = $platform;
            $app->save();

            $this->rollbackTo($app, SetupStep::PLATFORM);
        });
    }
}
