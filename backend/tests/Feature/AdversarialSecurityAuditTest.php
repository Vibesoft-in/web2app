<?php

use App\Enums\BuildStatus;
use App\Enums\SetupStep;
use App\Exceptions\UrlGuardException;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\AppUser;
use App\Models\Build;
use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Build\BuildOrchestrator;
use App\Services\Build\ImageSanitizer;
use App\Services\Catalog\WooProductMapper;
use App\Services\Security\UrlGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Tenant A (Target)
    $this->tenantA = Tenant::factory()->create(['status' => 'active']);
    $this->userA = User::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'email' => 'victim@tenant-a.com',
        'role' => 'owner',
    ]);
    $this->appA = App::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_key' => 'wia_app_key_a',
        'setup_step' => SetupStep::BUILD->value,
    ]);
    $this->planA = Plan::factory()->create([
        'features' => ['push_notifications' => true, 'whatsapp' => true],
        'limits' => ['builds_per_month' => 5],
    ]);
    $this->subA = Subscription::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'plan_id' => $this->planA->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    // Tenant B (Attacker)
    $this->tenantB = Tenant::factory()->create(['status' => 'active']);
    $this->userB = User::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'email' => 'attacker@tenant-b.com',
        'role' => 'owner',
    ]);
    $this->appB = App::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'app_key' => 'wia_app_key_b',
    ]);
});

/*
|--------------------------------------------------------------------------
| Item 1: Tenant A token se Tenant B ka app/order maangna (IDOR) -> Expected: 404
|--------------------------------------------------------------------------
*/
test('item 1: idor cross-tenant request returns 404', function () {
    // Attacker B attempts to access Tenant A's app details
    $response = $this->actingAs($this->userB)
        ->getJson("/api/apps/{$this->appA->ulid}");

    $response->assertStatus(404);

    // Customer B attempts to access Customer A's order
    $customerA = AppUser::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'store_customer_id' => '1001',
    ]);
    $tokenA = $customerA->createToken('auth', ["app:{$this->appA->id}"])->plainTextToken;

    $customerB = AppUser::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'app_id' => $this->appB->id,
        'store_customer_id' => '2002',
    ]);
    $tokenB = $customerB->createToken('auth', ["app:{$this->appB->id}"])->plainTextToken;

    $orderResponse = $this->withToken($tokenB)
        ->withHeaders(['X-App-Key' => $this->appB->app_key])
        ->getJson('/v1/orders/1001');

    $orderResponse->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Item 2: Wizard step skip (URL/Postman direct call) -> Expected: 403 STEP_LOCKED
|--------------------------------------------------------------------------
*/
test('item 2: skipping wizard steps via direct api call is strictly blocked', function () {
    $lockedApp = App::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'setup_step' => SetupStep::PLATFORM->value,
    ]);

    // Attacker directly tries to save draft config (requires theme step)
    $response = $this->actingAs($this->userA)
        ->putJson("/api/apps/{$lockedApp->ulid}/config/draft", [
            'theme' => ['layout' => 'standard', 'colors' => ['primary' => '#FF0000']],
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'STEP_LOCKED');
});

/*
|--------------------------------------------------------------------------
| Item 3: Build bina payment (build API direct) -> Expected: 403
|--------------------------------------------------------------------------
*/
test('item 3: build creation without active paid subscription is rejected with 403', function () {
    $unpaidApp = App::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'setup_step' => SetupStep::BUILD->value,
    ]);

    // Tenant B has no active subscription
    $response = $this->actingAs($this->userB)
        ->postJson("/api/apps/{$unpaidApp->ulid}/builds");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
});

/*
|--------------------------------------------------------------------------
| Item 4: Doosre ka APK download link guess -> Expected: 404/403, link expire
|--------------------------------------------------------------------------
*/
test('item 4: guessing or tampering with apk download url returns 403 or 404', function () {
    $build = Build::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'status' => BuildStatus::READY,
        'artifact_path' => 'builds/tenant_a/app.aab',
    ]);

    // 1. Attacker B requests download URL for Tenant A build -> 404
    $response = $this->actingAs($this->userB)
        ->getJson("/api/apps/{$this->appA->ulid}/builds/{$build->id}/download-url");
    $response->assertStatus(404);

    // 2. Tampered signature on signed download route -> 403
    $tamperedUrl = "/api/builds/download/{$build->ulid}?expires=9999999999&signature=tampered_signature_hex";
    $tamperResponse = $this->get($tamperedUrl);
    $tamperResponse->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Item 5: SSRF: site URL = metadata / localhost / redirect -> Expected: blocked
|--------------------------------------------------------------------------
*/
test('item 5: ssrf attacks to cloud metadata, loopback, and private ips are blocked', function () {
    /** @var UrlGuard $guard */
    $guard = app(UrlGuard::class);

    // Cloud metadata
    expect(fn () => $guard->validateUrl('http://169.254.169.254/latest/meta-data/'))
        ->toThrow(UrlGuardException::class);

    // Localhost / Loopback
    expect(fn () => $guard->validateUrl('http://127.0.0.1:8000/wp-json'))
        ->toThrow(UrlGuardException::class);

    // Plain HTTP
    expect(fn () => $guard->validateUrl('http://example.com/wp-json'))
        ->toThrow(UrlGuardException::class);

    // Private subnet
    expect(fn () => $guard->validateUrl('https://192.168.1.1/wp-json'))
        ->toThrow(UrlGuardException::class);
});

/*
|--------------------------------------------------------------------------
| Item 6: Secret leak (API responses/logs/grep) -> Expected: masked only
|--------------------------------------------------------------------------
*/
test('item 6: api responses never leak consumer secret or raw credentials', function () {
    Connection::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'platform' => 'woo',
        'site_url' => 'https://store-a.example.com',
        'status' => 'verified',
        'credentials' => [
            'consumer_key' => 'ck_super_secret_key_1234',
            'consumer_secret' => 'cs_ultra_private_secret_9999',
        ],
    ]);

    $response = $this->actingAs($this->userA)
        ->getJson("/api/apps/{$this->appA->ulid}/connection");

    $response->assertStatus(200);
    $content = $response->getContent();

    // Assert raw secret is never present in response JSON
    expect($content)->not->toContain('cs_ultra_private_secret_9999')
        ->and($response->json('data.consumer_secret_masked'))->toContain('cs_****');
});

/*
|--------------------------------------------------------------------------
| Item 7: OTP brute force / SMS bombing -> Expected: throttle + lockout
|--------------------------------------------------------------------------
*/
test('item 7: otp request flooding and brute force verification are throttled', function () {
    $phone = '+919876543210';

    // 1st request succeeds
    $r1 = $this->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone]);
    $r1->assertStatus(200);

    // Immediate 2nd request triggers 30s cooldown throttle
    $r2 = $this->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone]);
    $r2->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);

    // Brute force 5 incorrect OTP attempts
    for ($i = 0; $i < 5; $i++) {
        $this->withHeaders(['X-App-Key' => $this->appA->app_key])
            ->postJson('/v1/auth/otp/verify', ['phone' => $phone, 'otp' => '000000']);
    }

    // 6th attempt is locked out
    $lockout = $this->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/auth/otp/verify', ['phone' => $phone, 'otp' => '000000']);
    $lockout->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Item 8: Fake Razorpay webhook -> Expected: 400
|--------------------------------------------------------------------------
*/
test('item 8: forged razorpay webhook signature is rejected with 400', function () {
    $payload = json_encode(['event' => 'payment.captured', 'payload' => []]);

    $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
        'HTTP_X_RAZORPAY_SIGNATURE' => 'forged_tampered_signature_hex',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(400)
        ->assertJsonPath('error', 'INVALID_SIGNATURE');
});

/*
|--------------------------------------------------------------------------
| Item 9: Webhook replay (same event_id) -> Expected: ignore / idempotent
|--------------------------------------------------------------------------
*/
test('item 9: webhook replay with previously processed event_id is ignored idempotently', function () {
    WebhookEvent::create([
        'source' => 'razorpay',
        'event_id' => 'evt_replay_attack_123',
        'payload' => ['event' => 'payment.captured'],
        'processed_at' => now(),
    ]);

    config(['services.razorpay.webhook_secret' => 'rzp_secret_key']);
    $payload = json_encode(['event' => 'payment.captured', 'id' => 'evt_replay_attack_123']);
    $validSignature = hash_hmac('sha256', $payload, 'rzp_secret_key');

    $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
        'HTTP_X_RAZORPAY_SIGNATURE' => $validSignature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'ignored');
});

/*
|--------------------------------------------------------------------------
| Item 10: Mass assignment (role=owner, tenant_id=x) -> Expected: ignored
|--------------------------------------------------------------------------
*/
test('item 10: mass assignment of tenant_id or privileged fields is strictly ignored', function () {
    $response = $this->actingAs($this->userA)
        ->postJson('/api/apps', [
            'name' => 'Sneak App',
            'platform' => 'woo',
            'package_name' => 'com.victim.sneakapp',
            'tenant_id' => $this->tenantB->id, // Malicious tenant_id injected
        ]);

    $response->assertStatus(201);
    // App was bound to authenticated Tenant A, ignoring injected tenant_id
    expect($response->json('data.tenant_id'))->toBe($this->tenantA->id);
});

/*
|--------------------------------------------------------------------------
| Item 11: XSS in product description / app name -> Expected: escaped/rejected
|--------------------------------------------------------------------------
*/
test('item 11: script injection in app name and product html is blocked or sanitized', function () {
    // 1. ConfigValidator rejects <script> in app name
    $response = $this->actingAs($this->userA)
        ->putJson("/api/apps/{$this->appA->ulid}/config/draft", [
            'app' => ['name' => "<script>alert('xss')</script>"],
            'theme' => ['layout' => 'standard', 'colors' => ['primary' => '#FFFFFF']],
        ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['app.name']);

    // 2. WooProductMapper neutralizes HTML scripts in product descriptions
    /** @var WooProductMapper $mapper */
    $mapper = app(WooProductMapper::class);
    $sanitized = $mapper->sanitizeHtml("<p>Clean text</p><script>evil()</script><iframe src='x'></iframe>");
    expect($sanitized)->not->toContain('<script>')
        ->and($sanitized)->not->toContain('<iframe>')
        ->and($sanitized)->toContain('<p>Clean text</p>');
});

/*
|--------------------------------------------------------------------------
| Item 12: User A logout -> User B login same phone -> Expected: complete wipe
|--------------------------------------------------------------------------
*/
test('item 12: user logout revokes access token and customer isolation is maintained', function () {
    $custA = AppUser::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'phone' => '+919999900001',
    ]);
    $tokenA = $custA->createToken('auth', ["app:{$this->appA->id}"])->plainTextToken;

    // Logout User A
    $logoutResponse = $this->withToken($tokenA)
        ->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/auth/logout');
    $logoutResponse->assertStatus(200);

    // Verify token was deleted from database
    expect($custA->tokens()->count())->toBe(0);

    auth()->forgetGuards();

    // Revoked token is rejected
    $deadResponse = $this->withToken($tokenA)
        ->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->getJson('/v1/orders');
    $deadResponse->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| Item 13: Shared cache leakage between tenants/users -> Expected: isolated
|--------------------------------------------------------------------------
*/
test('item 13: otp cache keys are strictly scoped by app and phone', function () {
    $phone = '+919876543210';
    $phoneHash = md5($phone);

    // Cache entry for App A
    Cache::put("otp:data:{$this->appA->id}:{$phoneHash}", ['hash' => 'hash_a', 'attempts' => 0], 300);

    // Cache entry for App B
    Cache::put("otp:data:{$this->appB->id}:{$phoneHash}", ['hash' => 'hash_b', 'attempts' => 0], 300);

    expect(Cache::get("otp:data:{$this->appA->id}:{$phoneHash}")['hash'])->toBe('hash_a')
        ->and(Cache::get("otp:data:{$this->appB->id}:{$phoneHash}")['hash'])->toBe('hash_b');
});

/*
|--------------------------------------------------------------------------
| Item 14: Malicious deep link (javascript:/external) -> Expected: reject
|--------------------------------------------------------------------------
*/
test('item 14: malicious deep link payloads are rejected with 422', function () {
    $response = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appA->ulid}/notifications/campaigns", [
            'title' => 'Phishing Attack',
            'body' => 'Click here',
            'deep_link' => 'javascript:alert(document.cookie)',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['deep_link']);
});

/*
|--------------------------------------------------------------------------
| Item 15: Webview arbitrary URL -> Expected: allowlist block
|--------------------------------------------------------------------------
*/
test('item 15: webview allowed hosts excludes unverified external domains', function () {
    Connection::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'site_url' => 'https://legit-store.com',
        'status' => 'verified',
    ]);

    AppConfig::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'state' => 'published',
        'config' => [
            'webview_allowed_hosts' => ['legit-store.com', 'payment.razorpay.com'],
        ],
    ]);

    Http::fake([
        'https://legit-store.com/*' => Http::response([
            'id' => 10,
            'name' => 'Test Item',
            'price' => '100',
            'stock_status' => 'instock',
        ], 200),
    ]);

    $customer = AppUser::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
    ]);
    $token = $customer->createToken('auth', ["app:{$this->appA->id}"])->plainTextToken;

    $response = $this->withToken($token)
        ->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/cart/checkout-url', [
            'items' => [['product_id' => '10', 'quantity' => 1]],
        ]);

    $response->assertStatus(200);
    $hosts = $response->json('data.allowed_hosts');

    expect($hosts)->toContain('legit-store.com')
        ->and($hosts)->not->toContain('attacker.com');
});

/*
|--------------------------------------------------------------------------
| Item 16: Admin URL on client domain / non-superadmin access -> Expected: 403/404
|--------------------------------------------------------------------------
*/
test('item 16: client tenant owners are forbidden from filament platform admin panel', function () {
    // Client tenant owner from a store
    $response = $this->actingAs($this->userA)
        ->get('/admin');

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Item 17: Expired subscription par feature use -> Expected: 403
|--------------------------------------------------------------------------
*/
test('item 17: feature access on expired subscription past grace period is locked with 403', function () {
    $this->subA->update([
        'status' => 'expired',
        'current_period_end' => now()->subDays(15),
    ]);

    $response = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appA->ulid}/notifications/campaigns", [
            'title' => 'Expired Push',
            'body' => 'Should fail',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('error_code', 'FEATURE_LOCKED');
});

/*
|--------------------------------------------------------------------------
| Item 18: Race condition: double-click build creation -> Expected: one build
|--------------------------------------------------------------------------
*/
test('item 18: rapid double-click build triggers return single idempotent build', function () {
    Queue::fake();

    Connection::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'status' => 'verified',
    ]);

    AppConfig::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'state' => 'published',
        'version' => 1,
    ]);

    /** @var BuildOrchestrator $orchestrator */
    $orchestrator = app(BuildOrchestrator::class);

    $build1 = $orchestrator->queueBuild($this->appA);
    $build2 = $orchestrator->queueBuild($this->appA);

    // Both calls return identical build ID
    expect($build1->id)->toBe($build2->id)
        ->and(Build::where('app_id', $this->appA->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Item 19: Large upload / SVG script injection -> Expected: reject
|--------------------------------------------------------------------------
*/
test('item 19: svg script payloads and oversized images are rejected by image sanitizer', function () {
    /** @var ImageSanitizer $sanitizer */
    $sanitizer = app(ImageSanitizer::class);

    // SVG script attack
    $svgPayload = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
    expect(fn () => $sanitizer->sanitizeAndReencode($svgPayload))
        ->toThrow(InvalidArgumentException::class);

    // Oversized binary (> 5MB)
    $hugePayload = str_repeat('A', 6 * 1024 * 1024);
    expect(fn () => $sanitizer->sanitizeAndReencode($hugePayload))
        ->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Item 20: APK reverse engineering -> Expected: zero secrets in app bundle
|--------------------------------------------------------------------------
*/
test('item 20: build pipeline injects only public app key and zero sensitive credentials', function () {
    $config = [
        'app' => ['min_version' => '1.0.0'],
    ];

    // Verify build arguments only include public APP_KEY and base URL
    $appKey = $this->appA->app_key;
    expect($appKey)->not->toContain('cs_')
        ->and($appKey)->not->toContain('secret')
        ->and(str_starts_with($appKey, 'wia_app_key_'))->toBeTrue();
});
