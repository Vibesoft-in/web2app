<?php

use App\Models\App;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->storeApp = App::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'My Woo App',
        'platform' => 'woo',
        'package_name' => 'com.mywoo.app',
        'app_key' => 'wia_test_app_key_1',
        'setup_step' => 'connect',
    ]);

    Sanctum::actingAs($this->user, ['tenant:'.$this->tenant->id]);
});

test('successful connection verify advances app step and excludes raw secrets from response', function () {
    Http::fake([
        'https://mywooshop.com/wp-json/wc/v3/system_status' => Http::response([
            'environment' => ['version' => '8.5.1'],
            'settings' => ['store_name' => 'Awesome Store', 'currency' => 'INR'],
        ], 200, ['Content-Type' => 'application/json']),
        'https://mywooshop.com/wp-json/wc/v3/products?per_page=1' => Http::response([
            ['id' => 1],
        ], 200, [
            'Content-Type' => 'application/json',
            'X-WP-Total' => '88',
        ]),
    ]);

    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'https://mywooshop.com',
        'consumer_key' => 'ck_1234567890abcdef',
        'consumer_secret' => 'cs_0987654321fedcba',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Store connection verified successfully.')
        ->assertJsonPath('data.diagnostics.store_name', 'Awesome Store')
        ->assertJsonPath('data.diagnostics.products_count', 88)
        ->assertJsonPath('data.diagnostics.currency', 'INR')
        ->assertJsonPath('data.diagnostics.wc_version', '8.5.1')
        ->assertJsonPath('data.connection.status', 'verified')
        ->assertJsonPath('data.connection.consumer_key_masked', 'ck_****cdef')
        ->assertJsonPath('data.connection.consumer_secret_masked', 'cs_****masked');

    // CRITICAL: Assert raw secrets are NEVER leaked in response JSON
    $json = $response->getContent();
    expect($json)->not->toContain('cs_0987654321fedcba')
        ->and($json)->not->toContain('1234567890');

    // Assert app setup step advanced
    expect($this->storeApp->fresh()->setup_step)->toBe('analysis')
        ->and($this->storeApp->fresh()->status)->toBe('verified');
});

test('connection verify returns NOT_HTTPS when given plain http url', function () {
    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'http://insecure-store.com',
        'consumer_key' => 'ck_1234567890abcdef',
        'consumer_secret' => 'cs_0987654321fedcba',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'NOT_HTTPS');
});

test('connection verify returns INVALID_KEYS when store returns 401', function () {
    Http::fake([
        'https://mywooshop.com/wp-json/wc/v3/system_status' => Http::response([
            'code' => 'woocommerce_rest_cannot_view',
            'message' => 'Consumer secret is invalid.',
        ], 401, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'https://mywooshop.com',
        'consumer_key' => 'ck_wrongkey12345678',
        'consumer_secret' => 'cs_wrongsecret987654',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_KEYS');
});

test('connection verify returns INSUFFICIENT_PERMISSION when store returns 403', function () {
    Http::fake([
        'https://mywooshop.com/wp-json/wc/v3/system_status' => Http::response([
            'code' => 'woocommerce_rest_cannot_view',
            'message' => 'Sorry, you cannot view resources.',
        ], 403, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'https://mywooshop.com',
        'consumer_key' => 'ck_read_only_key123',
        'consumer_secret' => 'cs_read_only_sec456',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_PERMISSION');
});

test('connection verify returns REST_BLOCKED when endpoint returns 404 or HTML', function () {
    Http::fake([
        'https://mywooshop.com/wp-json/wc/v3/system_status' => Http::response('<html>Not Found</html>', 404, ['Content-Type' => 'text/html']),
        'https://mywooshop.com/wp-json/wc/v3/products*' => Http::response('<html>Not Found</html>', 404, ['Content-Type' => 'text/html']),
    ]);

    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'https://mywooshop.com',
        'consumer_key' => 'ck_testkey12345678',
        'consumer_secret' => 'cs_testsecret987654',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'REST_BLOCKED');
});

test('connection verify returns URL_UNREACHABLE on connection failure or SSRF attempt', function () {
    // Attempting internal IP through site_url
    $response = $this->postJson("/api/apps/{$this->storeApp->ulid}/connection/verify", [
        'site_url' => 'https://127.0.0.1',
        'consumer_key' => 'ck_testkey12345678',
        'consumer_secret' => 'cs_testsecret987654',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'URL_UNREACHABLE');
});

test('cross-tenant verify request on another tenant app returns strict 404', function () {
    $foreignTenant = Tenant::factory()->create();
    $foreignApp = App::create([
        'tenant_id' => $foreignTenant->id,
        'name' => 'Foreign App',
        'platform' => 'woo',
        'package_name' => 'com.foreign.app',
        'app_key' => 'wia_foreign_key_1',
    ]);

    $response = $this->postJson("/api/apps/{$foreignApp->ulid}/connection/verify", [
        'site_url' => 'https://mywooshop.com',
        'consumer_key' => 'ck_testkey12345678',
        'consumer_secret' => 'cs_testsecret987654',
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
