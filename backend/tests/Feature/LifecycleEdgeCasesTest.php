<?php

namespace Tests\Feature;

use App\Enums\BuildStatus;
use App\Enums\SetupStep;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\Build;
use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Connectors\ConnectorFactory;
use App\Services\Connectors\ConnectorInterface;
use App\Support\AppContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class LifecycleEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected App $clientApp;

    protected function setUp(): void
    {
        parent::setUp();

        TenantContext::forget();
        AppContext::forget();

        Storage::fake('local');

        $this->tenant = Tenant::create([
            'name' => 'Acme Edge Store',
            'slug' => 'acme-edge-store',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Operator',
            'email' => 'operator@acme.com',
            'password' => bcrypt('password123'),
        ]);

        $this->clientApp = App::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Mobile App',
            'package_name' => 'com.acme.mobile',
            'app_key' => 'ak_edge_'.bin2hex(random_bytes(8)),
            'platform' => 'woo',
            'setup_step' => 'platform',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($this->user, ['tenant:'.$this->tenant->id]);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();
        AppContext::forget();

        parent::tearDown();
    }

    /**
     * Edge case 1: Preview step when store is down, network fails, or keys are revoked.
     * Must return structured 502 with code STORE_UNREACHABLE instead of unhandled 500 crash.
     */
    public function test_preview_handles_store_down_or_revoked_keys_gracefully_with_502_not_500(): void
    {
        Connection::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'platform' => 'woo',
            'site_url' => 'https://mystore.example.com',
            'credentials' => [
                'consumer_key' => 'ck_revoked',
                'consumer_secret' => 'cs_revoked',
            ],
            'status' => 'verified',
        ]);

        $this->clientApp->update([
            'setup_step' => 'preview',
            'status' => 'configured',
        ]);

        // Mock connector to simulate store down / keys revoked exception
        $mockConnector = Mockery::mock(ConnectorInterface::class);
        $mockConnector->shouldReceive('getProducts')
            ->andThrow(new \RuntimeException('cURL error 28: Connection timed out or 401 Unauthorized'));

        $factory = Mockery::mock(ConnectorFactory::class);
        $factory->shouldReceive('make')
            ->andReturn($mockConnector);

        $this->app->instance(ConnectorFactory::class, $factory);

        $response = $this->getJson("/api/apps/{$this->clientApp->ulid}/preview-data");

        // Must return 502 Bad Gateway with code STORE_UNREACHABLE, NOT 500
        $response->assertStatus(502);
        $response->assertJson([
            'error' => [
                'code' => 'STORE_UNREACHABLE',
            ],
        ]);
    }

    /**
     * Edge case 2: Two tabs open or two users in one tenant editing the same config.
     * Optimistic concurrency check must reject stale writes with 409 VERSION_CONFLICT.
     */
    public function test_config_save_draft_detects_concurrent_edit_conflict_and_returns_409(): void
    {
        $this->clientApp->update(['setup_step' => 'theme']);

        // Initial draft created at version 1
        $draft = AppConfig::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'version' => 1,
            'state' => 'draft',
            'config' => [
                'schema_version' => 1,
                'theme' => [
                    'layout' => 'theme_a',
                    'colors' => [
                        'primary' => '#111827',
                        'secondary' => '#4B5563',
                    ],
                    'radius' => 8,
                    'font' => 'Inter',
                ],
                'features' => ['wishlist' => true],
            ],
        ]);

        // Tab 1 loads config (version 1) and saves changes first with base_version = 1
        $responseTab1 = $this->putJson("/api/apps/{$this->clientApp->ulid}/config/draft", [
            'base_version' => 1,
            'config' => [
                'schema_version' => 1,
                'theme' => [
                    'layout' => 'theme_a',
                    'colors' => [
                        'primary' => '#333333',
                        'secondary' => '#444444',
                    ],
                    'radius' => 8,
                    'font' => 'Inter',
                ],
                'features' => ['wishlist' => true],
            ],
        ]);

        $responseTab1->assertStatus(200);

        // Server version should now be incremented to 2
        $freshDraft = $draft->fresh();
        $this->assertEquals(2, $freshDraft->version);

        // Tab 2 was also open with stale base_version = 1, and now attempts to save
        $responseTab2 = $this->putJson("/api/apps/{$this->clientApp->ulid}/config/draft", [
            'base_version' => 1, // Stale version!
            'config' => [
                'schema_version' => 1,
                'theme' => [
                    'layout' => 'theme_a',
                    'colors' => [
                        'primary' => '#999999',
                        'secondary' => '#888888',
                    ],
                    'radius' => 8,
                    'font' => 'Inter',
                ],
                'features' => ['wishlist' => true],
            ],
        ]);

        // Tab 2 must be rejected with 409 Conflict
        $responseTab2->assertStatus(409);
        $responseTab2->assertJson([
            'error' => [
                'code' => 'VERSION_CONFLICT',
            ],
        ]);
    }

    /**
     * Edge case 3: Double click on signup with same email.
     */
    public function test_signup_double_click_handles_duplicate_email_cleanly(): void
    {
        $payload = [
            'name' => 'Double Clicker',
            'email' => 'duplicate@acme.com',
            'company_name' => 'Acme Double',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // First click
        $res1 = $this->postJson('/api/auth/register', $payload);
        $res1->assertStatus(201);

        // Second click
        $res2 = $this->postJson('/api/auth/register', $payload);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['email']);
    }

    /**
     * Edge case 4: Back button / rollback from later step back to platform step.
     * Invalidation: must reset connection credentials and unpublish published configs.
     */
    public function test_back_button_rollback_to_platform_invalidates_connection_and_blocks_build(): void
    {
        Connection::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'platform' => 'woo',
            'site_url' => 'https://mystore.example.com',
            'credentials' => ['consumer_key' => 'ck_test', 'consumer_secret' => 'cs_test'],
            'status' => 'verified',
            'last_verified_at' => now(),
        ]);

        AppConfig::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'version' => 1,
            'state' => 'published',
            'config' => [
                'theme' => ['layout' => 'theme_a', 'colors' => ['primary' => '#112233']],
            ],
            'published_at' => now(),
        ]);

        $this->clientApp->update([
            'setup_step' => SetupStep::PREVIEW->value,
            'status' => 'configured',
        ]);

        // User hits Back button in browser back to Platform step
        $rollbackResponse = $this->putJson("/api/apps/{$this->clientApp->ulid}/setup-step", [
            'step' => SetupStep::PLATFORM->value,
        ]);

        $rollbackResponse->assertStatus(200);

        // Verify downstream artifacts are invalidated
        $this->clientApp->refresh();
        $this->assertEquals(SetupStep::PLATFORM->value, $this->clientApp->setup_step);

        $connection = $this->clientApp->connection()->first();
        $this->assertEquals('not_started', $connection->status);
        $this->assertNull($connection->credentials);

        $hasPublished = $this->clientApp->configs()->where('state', 'published')->exists();
        $this->assertFalse($hasPublished);

        // Attempting to build must now be rejected with 403
        $buildResponse = $this->postJson("/api/apps/{$this->clientApp->ulid}/builds");

        $buildResponse->assertStatus(403);
        $buildResponse->assertJson([
            'error' => [
                'code' => 'BUILD_NOT_ALLOWED',
            ],
        ]);
    }

    /**
     * Edge case 5: Double click on "Start Build".
     * Must return existing active build idempotently without spawning duplicate jobs.
     */
    public function test_double_click_on_build_returns_existing_active_build_idempotently(): void
    {
        Queue::fake();

        // Prepare verified connection
        Connection::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'platform' => 'woo',
            'site_url' => 'https://mystore.example.com',
            'credentials' => ['consumer_key' => 'ck_test', 'consumer_secret' => 'cs_test'],
            'status' => 'verified',
            'last_verified_at' => now(),
        ]);

        // Published config
        AppConfig::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'version' => 1,
            'state' => 'published',
            'config' => ['app' => ['min_version' => '1.0.0']],
            'published_at' => now(),
        ]);

        // Active subscription with build quota
        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-edge',
            'price_monthly' => 2900,
            'price_yearly' => 29000,
            'limits' => ['builds_per_month' => 5],
            'features' => ['white_label' => true],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->clientApp->update(['setup_step' => 'build']);

        // First click
        $res1 = $this->postJson("/api/apps/{$this->clientApp->ulid}/builds");
        $res1->assertStatus(201);
        $buildId1 = $res1->json('data.id');

        // Immediate second click (simulating double click)
        $res2 = $this->postJson("/api/apps/{$this->clientApp->ulid}/builds");
        // Idempotent: returns the active build, same ID
        $res2->assertStatus(201);
        $buildId2 = $res2->json('data.id');

        $this->assertEquals($buildId1, $buildId2);
        $this->assertEquals(1, Build::where('app_id', $this->clientApp->id)->count());
    }

    /**
     * Edge case 6: Plan expired and grace period ended.
     */
    public function test_plan_expired_past_grace_period_blocks_build(): void
    {
        Connection::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'platform' => 'woo',
            'site_url' => 'https://mystore.example.com',
            'credentials' => ['consumer_key' => 'ck_test', 'consumer_secret' => 'cs_test'],
            'status' => 'verified',
            'last_verified_at' => now(),
        ]);

        AppConfig::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'version' => 1,
            'state' => 'published',
            'config' => ['app' => ['min_version' => '1.0.0']],
            'published_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-expired',
            'price_monthly' => 2900,
            'price_yearly' => 29000,
            'limits' => ['builds_per_month' => 5],
            'features' => ['white_label' => true],
            'is_active' => true,
        ]);

        // Subscription expired 10 days ago (grace period is 3 days)
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(7),
        ]);

        $this->clientApp->update(['setup_step' => 'build']);

        $response = $this->postJson("/api/apps/{$this->clientApp->ulid}/builds");

        $response->assertStatus(403);
        $response->assertJson([
            'error' => [
                'code' => 'BUILD_NOT_ALLOWED',
            ],
        ]);
    }

    /**
     * Edge case 7: Expired session / unauthenticated token on lifecycle endpoints.
     */
    public function test_expired_session_returns_401_on_protected_endpoints(): void
    {
        // Clear Sanctum authentication
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/apps/{$this->clientApp->ulid}/setup-state")
            ->assertStatus(401);

        $this->putJson("/api/apps/{$this->clientApp->ulid}/config/draft", ['config' => []])
            ->assertStatus(401);

        $this->getJson("/api/apps/{$this->clientApp->ulid}/preview-data")
            ->assertStatus(401);

        $this->postJson("/api/apps/{$this->clientApp->ulid}/builds")
            ->assertStatus(401);
    }

    /**
     * Edge case 8: Download endpoint edge cases (build not ready, expired signed URL).
     */
    public function test_download_returns_422_when_build_not_ready_and_403_when_signed_url_expired(): void
    {
        $build = Build::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->clientApp->id,
            'config_version' => 1,
            'status' => BuildStatus::BUILDING,
            'version_code' => 1,
            'version_name' => '1.0.0',
        ]);

        // 1. Download URL request before build is ready
        $res = $this->getJson("/api/apps/{$this->clientApp->ulid}/builds/{$build->ulid}/download-url");

        $res->assertStatus(422);
        $res->assertJson([
            'error' => [
                'code' => 'BUILD_NOT_READY',
            ],
        ]);

        // 2. Mark build ready with artifact
        $build->update([
            'status' => BuildStatus::READY,
            'artifact_path' => 'builds/test.aab',
        ]);
        Storage::disk('local')->put('builds/test.aab', 'dummy binary content');

        // Generate expired signed URL (expired 1 minute ago)
        $expiredUrl = URL::temporarySignedRoute(
            'builds.download',
            now()->subMinute(),
            ['ulid' => $build->ulid]
        );

        $expiredResponse = $this->getJson($expiredUrl);
        $expiredResponse->assertStatus(403);
    }
}
