<?php

use App\Enums\BuildStatus;
use App\Jobs\RunBuild;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\Build;
use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Build\BuildWorker;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    TenantContext::forget();
    Storage::fake('local');
});

test('build trigger is rejected with 403 BUILD_NOT_ALLOWED when setup requirements are missing', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    // 1. Connection missing / unverified
    $response = $this->postJson("/api/apps/{$app->ulid}/builds");
    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
    expect($response->json('error.message'))->toContain('Store connection must be verified');

    // 2. Add verified connection but no published config
    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://store.example.com',
        'status' => 'verified',
    ]);

    $response2 = $this->postJson("/api/apps/{$app->ulid}/builds");
    $response2->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
    expect($response2->json('error.message'))->toContain('App configuration must be published');

    // 3. Add published config but no active subscription
    AppConfig::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'version' => 1,
        'state' => 'published',
        'config' => ['app' => ['name' => 'Store App', 'min_version' => '1.0.0']],
    ]);

    $response3 = $this->postJson("/api/apps/{$app->ulid}/builds");
    $response3->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
    expect($response3->json('error.message'))->toContain('active subscription');
});

test('build triggers successfully when all requirements are met', function () {
    Queue::fake([RunBuild::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://store.example.com',
        'status' => 'verified',
    ]);

    AppConfig::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'version' => 1,
        'state' => 'published',
        'config' => ['app' => ['name' => 'Store App', 'min_version' => '1.0.0']],
    ]);

    $plan = Plan::factory()->create();
    Subscription::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
    ]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    $response = $this->postJson("/api/apps/{$app->ulid}/builds");

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Android build queued successfully.')
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.version_code', 1);

    expect(Build::where('app_id', $app->id)->count())->toBe(1);
    Queue::assertPushed(RunBuild::class);
});

test('idempotency: double click on build creates only one build', function () {
    Queue::fake([RunBuild::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://store.example.com',
        'status' => 'verified',
    ]);

    AppConfig::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'version' => 1,
        'state' => 'published',
        'config' => ['app' => ['name' => 'Store App', 'min_version' => '1.0.0']],
    ]);

    $plan = Plan::factory()->create();
    Subscription::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
    ]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    // Request 1
    $resp1 = $this->postJson("/api/apps/{$app->ulid}/builds");
    $resp1->assertStatus(201);
    $buildUlid1 = $resp1->json('data.ulid');

    // Request 2 (simulating duplicate click while first build is active)
    $resp2 = $this->postJson("/api/apps/{$app->ulid}/builds");
    $resp2->assertStatus(201);
    $buildUlid2 = $resp2->json('data.ulid');

    // Both requests return the same single build row
    expect($buildUlid1)->toBe($buildUlid2)
        ->and(Build::where('app_id', $app->id)->count())->toBe(1);

    Queue::assertPushed(RunBuild::class, 1);
});

test('per-tenant concurrency limit blocks simultaneous active builds across tenant apps', function () {
    Queue::fake([RunBuild::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $app1 = App::factory()->create(['tenant_id' => $tenant->id]);
    $app2 = App::factory()->create(['tenant_id' => $tenant->id]);

    $plan = Plan::factory()->create();
    Subscription::create([
        'tenant_id' => $tenant->id,
        'app_id' => null, // Tenant-wide subscription
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
    ]);

    // Setup App 1
    Connection::create(['tenant_id' => $tenant->id, 'app_id' => $app1->id, 'platform' => 'woo', 'site_url' => 'https://s1.example.com', 'status' => 'verified']);
    AppConfig::create(['tenant_id' => $tenant->id, 'app_id' => $app1->id, 'version' => 1, 'state' => 'published', 'config' => []]);

    // Setup App 2
    Connection::create(['tenant_id' => $tenant->id, 'app_id' => $app2->id, 'platform' => 'woo', 'site_url' => 'https://s2.example.com', 'status' => 'verified']);
    AppConfig::create(['tenant_id' => $tenant->id, 'app_id' => $app2->id, 'version' => 1, 'state' => 'published', 'config' => []]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    // App 1 starts building
    $this->postJson("/api/apps/{$app1->ulid}/builds")->assertStatus(201);

    // App 2 tries to build while App 1 is still active -> blocked by concurrency limit
    $resp = $this->postJson("/api/apps/{$app2->ulid}/builds");
    $resp->assertStatus(429)
        ->assertJsonPath('error.code', 'CONCURRENCY_LIMIT_REACHED');
});

test('tenant A cannot access or download tenant B builds', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $appA = App::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $appB = App::factory()->create(['tenant_id' => $tenantB->id]);

    $buildB = Build::create([
        'tenant_id' => $tenantB->id,
        'app_id' => $appB->id,
        'status' => BuildStatus::READY,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
        'artifact_path' => 'tenants/b/artifact.aab',
    ]);

    Sanctum::actingAs($userA, ['tenant:'.$tenantA->id]);

    // Attempt to access App B's build
    $resp = $this->getJson("/api/apps/{$appB->ulid}/builds/{$buildB->ulid}");
    $resp->assertStatus(404);

    // Attempt to request download URL
    $resp2 = $this->getJson("/api/apps/{$appB->ulid}/builds/{$buildB->ulid}/download-url");
    $resp2->assertStatus(404);
});

test('download-url returns expiring signed URL and streams artifact', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $app = App::factory()->create(['tenant_id' => $tenant->id, 'package_name' => 'com.store.download']);

    $artifactPath = "tenants/{$tenant->id}/apps/{$app->id}/builds/sample.aab";
    Storage::disk('local')->put($artifactPath, 'MOCK_BINARY_AAB_CONTENT');

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::READY,
        'config_version' => 1,
        'version_code' => 3,
        'version_name' => '1.2.0',
        'artifact_path' => $artifactPath,
    ]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    $response = $this->getJson("/api/apps/{$app->ulid}/builds/{$build->ulid}/download-url");
    $response->assertStatus(200);

    $downloadUrl = $response->json('data.download_url');
    expect($downloadUrl)->not->toBeNull()
        ->and($downloadUrl)->toContain('expires=')
        ->and($downloadUrl)->toContain('signature=');

    // Accessing the valid signed URL downloads the file
    $streamResp = $this->get($downloadUrl);
    $streamResp->assertStatus(200);
    expect($streamResp->streamedContent())->toBe('MOCK_BINARY_AAB_CONTENT');

    // Accessing tampered URL is rejected with 403
    $tamperedUrl = $downloadUrl.'tampered';
    $tamperedResp = $this->get($tamperedUrl);
    $tamperedResp->assertStatus(403);
});

test('full build worker execution updates build to ready, uploads artifact, and sanitizes logs', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'package_name' => 'com.secure.brand',
        'name' => 'Brand Store',
        'app_key' => 'wia_secret_app_key_test_123',
    ]);

    AppConfig::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'version' => 1,
        'state' => 'published',
        'config' => [
            'app' => ['name' => 'Brand Store', 'min_version' => '1.0.0'],
        ],
    ]);

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::QUEUED,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    /** @var BuildWorker $worker */
    $worker = app(BuildWorker::class);
    $worker->execute($build);

    $freshBuild = $build->fresh();
    expect($freshBuild->status)->toBe(BuildStatus::READY)
        ->and($freshBuild->artifact_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($freshBuild->artifact_path))->toBeTrue()
        ->and($freshBuild->logs_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($freshBuild->logs_path))->toBeTrue();

    // Verify logs do not contain raw secrets
    $logsContent = (string) Storage::disk('local')->get($freshBuild->logs_path);
    expect($logsContent)->not->toContain('wia_secret_app_key_test_123')
        ->and($logsContent)->toContain('[APP_KEY_REDACTED]');
});
