<?php

namespace App\Services\Build;

use App\Enums\BuildStatus;
use App\Exceptions\BuildException;
use App\Jobs\RunBuild;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\Build;
use App\Services\Setup\AppSetupStateMachine;
use Illuminate\Support\Facades\DB;

class BuildOrchestrator
{
    public const MAX_CONCURRENT_BUILDS_PER_TENANT = 1;

    public function __construct(
        protected AppSetupStateMachine $stateMachine,
    ) {}

    /**
     * Queue a new Android build with idempotency and concurrency protection.
     */
    public function queueBuild(App $app): Build
    {
        // 1. Permission and setup readiness check
        $blockingReasons = $this->stateMachine->getBuildBlockingReasons($app);
        if (! empty($blockingReasons)) {
            throw new BuildException(
                errorCode: 'BUILD_NOT_ALLOWED',
                message: 'Build not allowed: '.implode(' ', $blockingReasons),
                statusCode: 403
            );
        }

        return DB::transaction(function () use ($app) {
            // Lock app row to guarantee race condition / double-click protection
            App::where('id', $app->id)->lockForUpdate()->first();

            $activeStatuses = ['queued', 'preparing', 'building', 'signing'];

            // 2. Double-click Idempotency check:
            // If an active build already exists for this app, return it idempotently
            /** @var Build|null $existingActive */
            $existingActive = Build::where('app_id', $app->id)
                ->whereIn('status', $activeStatuses)
                ->latest()
                ->first();

            if ($existingActive) {
                return $existingActive;
            }

            // 3. Per-tenant concurrency check
            $tenantActiveCount = Build::where('tenant_id', $app->tenant_id)
                ->whereIn('status', $activeStatuses)
                ->count();

            if ($tenantActiveCount >= self::MAX_CONCURRENT_BUILDS_PER_TENANT) {
                throw new BuildException(
                    errorCode: 'CONCURRENCY_LIMIT_REACHED',
                    message: 'Your tenant has reached the maximum allowed concurrent build limit ('.self::MAX_CONCURRENT_BUILDS_PER_TENANT.'). Please wait for current builds to finish.',
                    statusCode: 429
                );
            }

            // 4. Pin config snapshot version and increment version code
            /** @var AppConfig|null $publishedConfig */
            $publishedConfig = $app->configs()
                ->where('state', 'published')
                ->latest('version')
                ->first();

            $configVersion = $publishedConfig?->version ?? 1;
            $latestVersionCode = (int) Build::where('app_id', $app->id)->max('version_code');
            $nextVersionCode = $latestVersionCode + 1;
            $versionName = (string) ($publishedConfig?->config['app']['min_version'] ?? '1.0.0');

            // 5. Create Build row in queued status
            $build = Build::create([
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'config_version' => $configVersion,
                'status' => BuildStatus::QUEUED,
                'version_code' => $nextVersionCode,
                'version_name' => $versionName,
            ]);

            $app->status = 'building';
            $app->save();

            // 6. Dispatch background job
            RunBuild::dispatch($build->id);

            return $build;
        });
    }
}
