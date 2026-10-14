<?php

namespace App\Jobs;

use App\Models\Build;
use App\Services\Build\BuildWorker;
use App\Support\AppContext;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    public function __construct(
        public int $buildId,
    ) {
        $this->onQueue('builds');
    }

    /**
     * Execute the build job.
     */
    public function handle(BuildWorker $worker): void
    {
        /** @var Build|null $build */
        $build = Build::withoutGlobalScopes()->with(['app.tenant', 'app.keystore'])->find($this->buildId);
        if (! $build || ! $build->app) {
            return;
        }

        $app = $build->app;
        $tenant = $app->tenant;

        if ($tenant) {
            TenantContext::set($tenant);
        }
        AppContext::set($app);

        try {
            $worker->execute($build);
        } finally {
            AppContext::forget();
            TenantContext::forget();
        }
    }
}
