<?php

use App\Jobs\SendPushBatchJob;
use App\Models\App;
use App\Models\AppUser;
use App\Models\NotificationCampaign;
use App\Models\Plan;
use App\Models\PushDevice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['status' => 'active']);
    $this->userA = User::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role' => 'owner',
    ]);
    $this->planWithPush = Plan::factory()->create([
        'features' => ['push_notifications' => true],
        'limits' => ['push_per_month' => 50000],
    ]);
    $this->appA = App::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_key' => 'wia_app_key_tenant_a',
    ]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'plan_id' => $this->planWithPush->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    $this->tenantB = Tenant::factory()->create(['status' => 'active']);
    $this->userB = User::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'role' => 'owner',
    ]);
    $this->appB = App::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'app_key' => 'wia_app_key_tenant_b',
    ]);
});

test('device token can be registered and unregistered via /v1/devices', function () {
    // 1. Register device
    $response = $this->withHeaders([
        'X-App-Key' => $this->appA->app_key,
    ])->postJson('/v1/devices', [
        'fcm_token' => 'fcm_sample_token_xyz_123',
        'platform' => 'android',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Push notification device registered successfully.');

    expect(PushDevice::where('app_id', $this->appA->id)->where('fcm_token', 'fcm_sample_token_xyz_123')->exists())->toBeTrue();

    // 2. Unregister device
    $unregisterResponse = $this->withHeaders([
        'X-App-Key' => $this->appA->app_key,
    ])->deleteJson('/v1/devices', [
        'fcm_token' => 'fcm_sample_token_xyz_123',
    ]);

    $unregisterResponse->assertStatus(200)
        ->assertJsonPath('message', 'Device unhooked successfully.');

    expect(PushDevice::where('app_id', $this->appA->id)->where('fcm_token', 'fcm_sample_token_xyz_123')->exists())->toBeFalse();
});

test('tenant isolation: device tokens and campaigns are isolated across tenants', function () {
    PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'fcm_token' => 'token_tenant_a_secret',
    ]);

    PushDevice::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'app_id' => $this->appB->id,
        'fcm_token' => 'token_tenant_b_secret',
    ]);

    // Tenant A attempts to access Tenant B's campaigns
    $response = $this->actingAs($this->userA)
        ->getJson("/api/apps/{$this->appB->ulid}/notifications/campaigns");

    $response->assertStatus(404);

    // Tenant A attempts to dispatch campaign on Tenant B's app
    $dispatchResponse = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appB->ulid}/notifications/campaigns", [
            'title' => 'Sneak Attack',
            'body' => 'Should fail',
        ]);

    $dispatchResponse->assertStatus(404);
});

test('campaign creation is gated by plan entitlement', function () {
    // Tenant B has plan WITHOUT push notifications
    $planWithoutPush = Plan::factory()->create([
        'features' => ['push_notifications' => false],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'plan_id' => $planWithoutPush->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->userB)
        ->postJson("/api/apps/{$this->appB->ulid}/notifications/campaigns", [
            'title' => 'Flash Sale',
            'body' => 'Get 50% off right now',
        ]);

    $response->assertStatus(403);
});

test('campaign creation validates deep link format against allowlist', function () {
    // Malicious external link -> rejected with 422
    $response = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appA->ulid}/notifications/campaigns", [
            'title' => 'Phishing Campaign',
            'body' => 'Click this link',
            'deep_link' => 'https://attacker.example.com/login',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['deep_link']);

    // Valid allowlisted format -> accepted with 201
    $validResponse = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appA->ulid}/notifications/campaigns", [
            'title' => 'New Product Alert',
            'body' => 'Check out our new summer sneakers',
            'deep_link' => 'product:sneakers-summer-42',
        ]);

    $validResponse->assertStatus(201)
        ->assertJsonPath('data.deep_link', 'product:sneakers-summer-42');
});

test('order-status webhook triggers automated push to customer devices', function () {
    Queue::fake([SendPushBatchJob::class]);

    $appUser = AppUser::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'store_customer_id' => '998877',
    ]);

    PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'app_user_id' => $appUser->id,
        'fcm_token' => 'fcm_order_customer_token',
    ]);

    $secret = hash('sha256', $this->appA->app_key.'_webhook_secret');
    $payload = json_encode([
        'id' => 4501,
        'status' => 'completed',
        'customer_id' => '998877',
        'total' => '1499.00',
    ]);
    $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $response = $this->call(
        'POST',
        "/webhooks/woo/{$this->appA->ulid}",
        [],
        [],
        [],
        [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.updated',
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'order_delivery_evt_77',
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(200);

    Queue::assertPushed(SendPushBatchJob::class, function (SendPushBatchJob $job) {
        return $job->appId === $this->appA->id
            && $job->tokens === ['fcm_order_customer_token']
            && $job->deepLink === 'order:4501'
            && str_contains($job->body, 'Completed');
    });
});

test('fcm service cleans up invalid tokens automatically', function () {
    // Create one valid device and one expired/invalid device
    $validDevice = PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'fcm_token' => 'valid_active_token_111',
    ]);

    $invalidDevice = PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'fcm_token' => 'unregistered_token_999',
    ]);

    $campaign = NotificationCampaign::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
    ]);

    /** @var SendPushBatchJob $job */
    $job = new SendPushBatchJob(
        appId: $this->appA->id,
        tokens: [$validDevice->fcm_token, $invalidDevice->fcm_token],
        title: 'Flash Sale',
        body: 'Check out today specials',
        deepLink: 'category:shoes',
        campaignId: $campaign->id
    );

    $job->handle(app(FcmServiceInterface::class), app(\App\Services\Billing\UsageEventService::class));

    // Valid device remains, invalid device was cleaned up!
    expect(PushDevice::where('fcm_token', $validDevice->fcm_token)->exists())->toBeTrue()
        ->and(PushDevice::where('fcm_token', $invalidDevice->fcm_token)->exists())->toBeFalse();

    // Campaign stats updated
    $freshCampaign = $campaign->fresh();
    expect($freshCampaign->sent_count)->toBe(1)
        ->and($freshCampaign->failed_count)->toBe(1);
});

test('order-status webhook for anonymous guest checkout does not push to random users', function () {
    Queue::fake([SendPushBatchJob::class]);

    // An arbitrary user exists for App A
    $randomUser = AppUser::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'store_customer_id' => '112233',
    ]);

    PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'app_user_id' => $randomUser->id,
        'fcm_token' => 'random_user_fcm_token',
    ]);

    // Anonymous guest order (customer_id: 0, no phone)
    $secret = hash('sha256', $this->appA->app_key.'_webhook_secret');
    $payload = json_encode([
        'id' => 7701,
        'status' => 'completed',
        'customer_id' => '0',
        'billing' => ['phone' => ''],
    ]);
    $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $response = $this->call(
        'POST',
        "/webhooks/woo/{$this->appA->ulid}",
        [],
        [],
        [],
        [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.updated',
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'guest_order_evt_99',
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(200);

    // Job must NOT be pushed to any user
    Queue::assertNothingPushed();
});

test('viewer role is blocked from creating notification campaigns', function () {
    $viewerUser = User::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role' => 'viewer',
    ]);

    $response = $this->actingAs($viewerUser)
        ->postJson("/api/apps/{$this->appA->ulid}/notifications/campaigns", [
            'title' => 'Sneak Promotion',
            'body' => 'Viewer trying to blast notifications',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('scheduled campaigns are dispatched by console scheduler command', function () {
    Queue::fake([SendPushBatchJob::class]);

    $device = PushDevice::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'fcm_token' => 'token_scheduled_receiver',
    ]);

    $scheduledCampaign = NotificationCampaign::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'title' => 'Scheduled Morning Drop',
        'body' => 'New arrivals are live!',
        'deep_link' => 'category:new_arrivals',
        'status' => 'scheduled',
        'scheduled_at' => now()->subMinute(),
    ]);

    // Run the scheduler command
    $this->artisan('notifications:dispatch-scheduled')
        ->assertSuccessful();

    // Campaign status updated to sent
    expect($scheduledCampaign->fresh()->status)->toBe('sent');

    Queue::assertPushed(SendPushBatchJob::class, function (SendPushBatchJob $job) use ($scheduledCampaign) {
        return $job->appId === $this->appA->id
            && $job->campaignId === $scheduledCampaign->id
            && $job->deepLink === 'category:new_arrivals';
    });
});

test('duplicate device registration is idempotent and updates existing record', function () {
    $token = 'fcm_idempotent_test_token_123';

    // 1st registration
    $this->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/devices', [
            'fcm_token' => $token,
            'platform' => 'android',
        ])->assertStatus(200);

    // 2nd registration for same app and token (e.g. app restart or token refresh)
    $this->withHeaders(['X-App-Key' => $this->appA->app_key])
        ->postJson('/v1/devices', [
            'fcm_token' => $token,
            'platform' => 'android',
        ])->assertStatus(200);

    expect(PushDevice::where('app_id', $this->appA->id)->where('fcm_token', $token)->count())->toBe(1);
});

