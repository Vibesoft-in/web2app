<?php

use App\Models\App;
use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\CredentialVault;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->vault = new CredentialVault;

    $this->storeApp = App::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'State Machine Store App',
        'platform' => 'woo',
        'package_name' => 'com.statemachine.app',
        'app_key' => 'wia_statemachine_app_key_1',
        'setup_step' => 'connect',
        'status' => 'draft',
    ]);

    Sanctum::actingAs($this->user, ['tenant:'.$this->tenant->id]);
});

test('skipping steps via direct API call is blocked with 403 STEP_LOCKED', function () {
    // App is currently at 'connect'. Accessing 'theme' step action directly must be blocked.
    $response = $this->putJson("/api/apps/{$this->storeApp->ulid}/config/draft", [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => ['primary' => '#000000'],
        ],
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'STEP_LOCKED')
        ->assertJsonPath('error.current_step', 'connect')
        ->assertJsonPath('error.required_step', 'theme');

    // Accessing preview-data when at connect step must also be blocked
    $previewResponse = $this->getJson("/api/apps/{$this->storeApp->ulid}/preview-data");
    $previewResponse->assertStatus(403)
        ->assertJsonPath('error.code', 'STEP_LOCKED')
        ->assertJsonPath('error.required_step', 'preview');
});

test('saving draft config validates schema and rejects tampered payloads with 422', function () {
    // Advance app to 'features' so 'theme' step is unlocked (order 4 <= current 3 + 1)
    $this->storeApp->update(['setup_step' => 'features']);

    // Attempt tampered config with non-hex color and insecure http URL
    $response = $this->putJson("/api/apps/{$this->storeApp->ulid}/config/draft", [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => [
                'primary' => 'invalid_named_color',
            ],
        ],
        'links' => [
            'privacy' => 'http://insecure-link.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['theme.colors.primary', 'links.privacy']);
});

test('valid draft config saves successfully and publishing creates version and advances step', function () {
    $this->storeApp->update(['setup_step' => 'features']);

    $validConfig = [
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
        'features' => [
            'wishlist' => true,
        ],
    ];

    // 1. Save draft
    $saveResponse = $this->putJson("/api/apps/{$this->storeApp->ulid}/config/draft", $validConfig);
    $saveResponse->assertStatus(200)
        ->assertJsonPath('data.state', 'draft')
        ->assertJsonPath('data.version', 1);

    expect($this->storeApp->fresh()->setup_step)->toBe('theme');

    // 2. Publish config
    $publishResponse = $this->postJson("/api/apps/{$this->storeApp->ulid}/config/publish");
    $publishResponse->assertStatus(200)
        ->assertJsonPath('data.state', 'published')
        ->assertJsonPath('data.version', 1);

    // App should be advanced to preview step
    expect($this->storeApp->fresh()->setup_step)->toBe('preview')
        ->and($this->storeApp->fresh()->status)->toBe('configured');
});

test('going back to platform step invalidates connection and reverts app status', function () {
    // Setup app at theme step with verified connection
    $this->storeApp->update(['setup_step' => 'theme', 'status' => 'verified']);

    $connection = Connection::create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->storeApp->id,
        'platform' => 'woo',
        'site_url' => 'https://mystore.example.com',
        'credentials' => [
            'consumer_key' => 'ck_test123',
            'consumer_secret' => 'cs_test456',
        ],
        'status' => 'verified',
        'last_verified_at' => now(),
    ]);

    // Rollback to platform
    $response = $this->putJson("/api/apps/{$this->storeApp->ulid}/setup-step", [
        'step' => 'platform',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.current_step', 'platform');

    // Connection must be reset to not_started and credentials wiped
    $freshConn = $connection->fresh();
    expect($freshConn->status)->toBe('not_started')
        ->and($freshConn->credentials)->toBeNull()
        ->and($freshConn->last_verified_at)->toBeNull();

    // App status reverted to draft
    expect($this->storeApp->fresh()->status)->toBe('draft')
        ->and($this->storeApp->fresh()->setup_step)->toBe('platform');
});

test('build_allowed is correctly computed from connection verified + config published + subscription active', function () {
    // Case A: Fresh app -> build_allowed false
    $resA = $this->getJson("/api/apps/{$this->storeApp->ulid}/setup-state");
    $resA->assertStatus(200)
        ->assertJsonPath('data.build_allowed', false)
        ->assertJsonPath('data.build_blocking_reasons.0', 'Store connection must be verified.');

    // Add verified connection
    Connection::create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->storeApp->id,
        'platform' => 'woo',
        'site_url' => 'https://mystore.example.com',
        'credentials' => ['consumer_key' => 'ck_1', 'consumer_secret' => 'cs_2'],
        'status' => 'verified',
    ]);

    // Case B: Connection verified, but config not published
    $resB = $this->getJson("/api/apps/{$this->storeApp->ulid}/setup-state");
    $resB->assertStatus(200)
        ->assertJsonPath('data.build_allowed', false)
        ->assertJsonPath('data.build_blocking_reasons.0', 'App configuration must be published.');

    // Add published config
    $this->storeApp->configs()->create([
        'tenant_id' => $this->tenant->id,
        'version' => 1,
        'state' => 'published',
        'config' => ['theme' => ['layout' => 'theme_a', 'colors' => ['primary' => '#000000']]],
        'published_at' => now(),
    ]);

    // Case C: Connection verified + config published, but subscription inactive
    $resC = $this->getJson("/api/apps/{$this->storeApp->ulid}/setup-state");
    $resC->assertStatus(200)
        ->assertJsonPath('data.build_allowed', false)
        ->assertJsonPath('data.build_blocking_reasons.0', 'An active subscription plan is required.');

    // Add active subscription
    $plan = Plan::create([
        'name' => 'Growth Plan',
        'slug' => 'growth',
        'price_monthly' => 2999.00,
        'features' => ['push_notifications' => true],
    ]);

    Subscription::create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->storeApp->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
    ]);

    // Case D: All 3 requirements satisfied -> build_allowed true!
    $resD = $this->getJson("/api/apps/{$this->storeApp->ulid}/setup-state");
    $resD->assertStatus(200)
        ->assertJsonPath('data.build_allowed', true)
        ->assertJsonPath('data.build_blocking_reasons', []);
});

test('preview-data endpoint returns real products from store connector when at preview step', function () {
    $this->storeApp->update(['setup_step' => 'preview', 'status' => 'configured']);

    Connection::create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->storeApp->id,
        'platform' => 'woo',
        'site_url' => 'https://mystore.example.com',
        'credentials' => [
            'consumer_key' => 'ck_testkey',
            'consumer_secret' => 'cs_testsecret',
        ],
        'status' => 'verified',
    ]);

    Http::fake([
        'https://mystore.example.com/wp-json/wc/v3/products/categories*' => Http::response([
            ['id' => 10, 'name' => 'Apparel', 'slug' => 'apparel', 'count' => 15],
        ], 200, ['Content-Type' => 'application/json']),
        'https://mystore.example.com/wp-json/wc/v3/products*' => Http::response([
            [
                'id' => 201,
                'name' => 'Organic Cotton Hoodie',
                'regular_price' => '2499.00',
                'price' => '2499.00',
                'stock_status' => 'instock',
                'images' => [
                    ['src' => 'https://mystore.example.com/wp-content/uploads/hoodie.jpg'],
                ],
                'categories' => [],
                'permalink' => 'https://mystore.example.com/product/hoodie',
            ],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->getJson("/api/apps/{$this->storeApp->ulid}/preview-data");

    $response->assertStatus(200)
        ->assertJsonPath('data.preview_mode', 'live_store')
        ->assertJsonPath('data.products.0.title', 'Organic Cotton Hoodie')
        ->assertJsonPath('data.categories.0.name', 'Apparel');
});

test('cross-tenant setup-state and preview-data requests return strict 404', function () {
    $foreignTenant = Tenant::factory()->create();
    $foreignApp = App::create([
        'tenant_id' => $foreignTenant->id,
        'name' => 'Foreign App',
        'platform' => 'woo',
        'package_name' => 'com.foreign.app2',
        'app_key' => 'wia_foreign_key_2',
        'setup_step' => 'preview',
    ]);

    // Tenant A accessing Tenant B's setup-state -> 404
    $this->getJson("/api/apps/{$foreignApp->ulid}/setup-state")
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');

    // Tenant A accessing Tenant B's preview-data -> 404
    $this->getJson("/api/apps/{$foreignApp->ulid}/preview-data")
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');

    // Tenant A saving config on Tenant B's app -> 404
    $this->putJson("/api/apps/{$foreignApp->ulid}/config/draft", [
        'theme' => ['layout' => 'theme_a', 'colors' => ['primary' => '#000000']],
    ])->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
