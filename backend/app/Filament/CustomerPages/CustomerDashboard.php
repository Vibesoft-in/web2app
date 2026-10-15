<?php

namespace App\Filament\CustomerPages;

use App\Enums\BuildStatus;
use App\Jobs\RunBuild;
use App\Models\App;
use App\Models\Build;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Build\BuildOrchestrator;
use App\Support\TenantContext;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.customer-dashboard';

    public ?int $previewAppId = null;

    public static function canAccess(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'app';
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $tenant = TenantContext::get();
        if (! $tenant && auth()->check() && auth()->user()?->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
        }

        $user = auth()->user();

        /** @var Collection<int, App> $apps */
        $apps = $tenant
            ? $tenant->apps()->with(['builds' => fn ($q) => $q->latest()])->latest('updated_at')->take(5)->get()
            : collect();

        $totalApps = $tenant ? $tenant->apps()->count() : 0;
        $totalBuilds = $tenant ? Build::where('tenant_id', $tenant->id)->count() : 0;

        $successfulBuilds = $tenant
            ? Build::where('tenant_id', $tenant->id)->where('status', BuildStatus::READY)->count()
            : 0;

        $pendingBuilds = $tenant
            ? Build::where('tenant_id', $tenant->id)->whereIn('status', [
                BuildStatus::QUEUED,
                BuildStatus::PREPARING,
                BuildStatus::BUILDING,
                BuildStatus::SIGNING,
            ])->count()
            : 0;

        $hasCreatedApp = $totalApps > 0;
        $hasBuiltApp = $totalBuilds > 0;
        $hasReadyBuild = $successfulBuilds > 0;

        $tasksCompleted = ($hasCreatedApp ? 1 : 0) + ($hasBuiltApp ? 1 : 0) + ($hasReadyBuild ? 1 : 0);
        $progressPercent = (int) round(($tasksCompleted / 3) * 100);

        $previewApp = $this->previewAppId ? $apps->firstWhere('id', $this->previewAppId) : null;
        $previewConfig = $previewApp
            ? ($previewApp->configs()->where('state', 'published')->latest('version')->first()?->config ?? [])
            : [];

        return [
            'user' => $user,
            'userName' => $user?->name ?? 'User',
            'userInitial' => strtoupper(substr((string) ($user?->name ?? 'U'), 0, 1)),
            'tenant' => $tenant,
            'apps' => $apps,
            'totalApps' => $totalApps,
            'totalBuilds' => $totalBuilds,
            'successfulBuilds' => $successfulBuilds,
            'pendingBuilds' => $pendingBuilds,
            'hasCreatedApp' => $hasCreatedApp,
            'hasBuiltApp' => $hasBuiltApp,
            'hasReadyBuild' => $hasReadyBuild,
            'tasksCompleted' => $tasksCompleted,
            'progressPercent' => $progressPercent,
            'previewApp' => $previewApp,
            'previewConfig' => $previewConfig,
        ];
    }

    public function triggerBuild(int $appId): void
    {
        $tenant = TenantContext::get();
        if (! $tenant && auth()->check() && auth()->user()?->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
        }

        if (! $tenant) {
            Notification::make()->title('Tenant context not found')->danger()->send();

            return;
        }

        $app = $tenant->apps()->find($appId);
        if (! $app) {
            Notification::make()->title('App not found')->danger()->send();

            return;
        }

        try {
            if (! $tenant->hasActiveSubscription($app->id)) {
                $plan = Plan::firstOrCreate(
                    ['slug' => 'starter'],
                    [
                        'name' => 'Starter Plan',
                        'price_monthly' => 0.00,
                        'features' => ['push_notifications' => true, 'whatsapp' => true],
                        'limits' => ['builds_per_month' => 10, 'max_apps' => 5],
                        'is_active' => true,
                    ]
                );

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'app_id' => $app->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'current_period_end' => now()->addDays(30),
                ]);
            }

            /** @var BuildOrchestrator $orchestrator */
            $orchestrator = app(BuildOrchestrator::class);
            $build = $orchestrator->queueBuild($app);

            if (empty(env('FLUTTER_BIN'))) {
                RunBuild::dispatchSync($build->id);

                Notification::make()
                    ->title('✅ APK Ready for Download!')
                    ->body("Android APK for '{$app->name}' (Build #{$build->version_code}) has been generated successfully. You can download it now!")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('⚡ Build Queued')
                    ->body("Android compilation queued for '{$app->name}'. Check 'Builds & Downloads' when complete.")
                    ->info()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Build Request Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openPreview(int $appId): void
    {
        $this->previewAppId = $appId;
    }

    public function closePreview(): void
    {
        $this->previewAppId = null;
    }

    public function downloadApk(int $appId): mixed
    {
        $tenant = TenantContext::get();
        if (! $tenant && auth()->check() && auth()->user()?->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
        }

        if (! $tenant) {
            Notification::make()->title('Tenant context not found')->danger()->send();

            return null;
        }

        $app = $tenant->apps()->find($appId);
        if (! $app) {
            Notification::make()->title('App not found')->danger()->send();

            return null;
        }

        $latestReady = $app->builds()->where('status', BuildStatus::READY)->latest()->first();
        if (! $latestReady) {
            Notification::make()->title('No completed build found for this app')->danger()->send();

            return null;
        }

        $path = $latestReady->artifact_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            $candidate = "tenants/{$app->tenant_id}/apps/{$app->id}/builds/{$latestReady->ulid}/app-release.apk";
            if (Storage::disk('local')->exists($candidate)) {
                $path = $candidate;
            }
        }

        if (! $path || ! Storage::disk('local')->exists($path)) {
            Notification::make()
                ->title('APK File Not Found')
                ->body('The generated artifact file is not available on disk. Please trigger a new build.')
                ->danger()
                ->send();

            return null;
        }

        $filename = Str::slug($app->name)."-v{$latestReady->version_name}-build{$latestReady->version_code}.apk";

        return response()->download(Storage::disk('local')->path($path), $filename, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
