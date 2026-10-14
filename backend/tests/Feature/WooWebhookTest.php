<?php

use App\Models\App;
use App\Models\Tenant;
use App\Models\WebhookEvent;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->storeApp = App::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Webhook Woo App',
        'platform' => 'woo',
        'package_name' => 'com.webhook.woo',
        'app_key' => 'wia_webhook_app_key_1',
    ]);

    $this->webhookSecret = hash('sha256', $this->storeApp->app_key.'_webhook_secret');
});

test('woo webhook with valid HMAC signature is accepted and stored idempotently', function () {
    $payload = json_encode([
        'id' => 1234,
        'name' => 'Updated Product Name',
        'price' => '25.00',
    ], JSON_THROW_ON_ERROR);

    $signature = base64_encode(hash_hmac('sha256', $payload, $this->webhookSecret, true));

    $response = $this->call(
        method: 'POST',
        uri: "/webhooks/woo/{$this->storeApp->ulid}",
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'product.updated',
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'delivery_event_001',
        ],
        content: $payload
    );

    $response->assertStatus(200)
        ->assertJsonPath('status', 'received');

    $this->assertDatabaseHas('webhook_events', [
        'source' => 'woo',
        'event_id' => 'delivery_event_001',
    ]);
});

test('woo webhook rejects invalid HMAC signature with 401', function () {
    $payload = json_encode(['id' => 999]);
    $invalidSignature = base64_encode(hash_hmac('sha256', $payload, 'wrong_secret', true));

    $response = $this->call(
        method: 'POST',
        uri: "/webhooks/woo/{$this->storeApp->ulid}",
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $invalidSignature,
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'product.updated',
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'delivery_event_bad',
        ],
        content: $payload
    );

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_SIGNATURE');
});

test('woo webhook rejects request missing signature header with 400', function () {
    $payload = json_encode(['id' => 999]);

    $response = $this->call(
        method: 'POST',
        uri: "/webhooks/woo/{$this->storeApp->ulid}",
        server: [
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $payload
    );

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'MISSING_SIGNATURE');
});

test('woo webhook safely ignores replay of previously processed delivery ID', function () {
    $payload = json_encode(['id' => 1234], JSON_THROW_ON_ERROR);
    $signature = base64_encode(hash_hmac('sha256', $payload, $this->webhookSecret, true));

    WebhookEvent::create([
        'source' => 'woo',
        'event_id' => 'delivery_duplicate_001',
        'payload' => ['id' => 1234],
        'processed_at' => now(),
    ]);

    $response = $this->call(
        method: 'POST',
        uri: "/webhooks/woo/{$this->storeApp->ulid}",
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'delivery_duplicate_001',
        ],
        content: $payload
    );

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Event already processed.');

    // Only 1 record in database, no duplicate entry created
    expect(WebhookEvent::where('event_id', 'delivery_duplicate_001')->count())->toBe(1);
});
