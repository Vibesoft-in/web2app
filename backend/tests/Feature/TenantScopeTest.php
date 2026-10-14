<?php

use App\Models\App;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    TenantContext::forget();
});

test('tenant A cannot see tenant B apps in apps listing', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    // Create app for Tenant A
    $appA = App::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Store App A',
        'platform' => 'woo',
        'package_name' => 'com.store.a',
        'app_key' => 'wia_test_key_a',
    ]);

    // Create app for Tenant B
    $appB = App::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Store App B',
        'platform' => 'woo',
        'package_name' => 'com.store.b',
        'app_key' => 'wia_test_key_b',
    ]);

    // Act as Tenant A
    Sanctum::actingAs($userA, ['tenant:'.$tenantA->id]);

    $response = $this->getJson('/api/apps');

    $response->assertStatus(200);

    $apps = $response->json('data');

    expect($apps)->toHaveCount(1)
        ->and($apps[0]['name'])->toBe('Store App A')
        ->and($apps[0]['id'])->toBe($appA->id);
});

test('tenant A requesting tenant B app by ULID receives a strict 404', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();

    $appB = App::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Secret App B',
        'platform' => 'woo',
        'package_name' => 'com.secret.b',
        'app_key' => 'wia_test_key_secret_b',
    ]);

    // Act as Tenant A
    Sanctum::actingAs($userA, ['tenant:'.$tenantA->id]);

    // Request Tenant B's app using its valid ULID
    $response = $this->getJson('/api/apps/'.$appB->ulid);

    // Must return 404, never 403 or 200
    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

test('creating an app automatically binds to the authenticated tenant_id and ignores request tenant_id manipulation', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    $tenantB = Tenant::factory()->create();

    Sanctum::actingAs($userA, ['tenant:'.$tenantA->id]);

    // Attempt mass assignment / tampering with tenant_id pointing to Tenant B
    $response = $this->postJson('/api/apps', [
        'name' => 'My New App',
        'platform' => 'woo',
        'package_name' => 'com.mynew.app',
        'tenant_id' => $tenantB->id,
    ]);

    $response->assertStatus(201);

    $createdApp = App::where('package_name', 'com.mynew.app')->first();

    expect($createdApp)->not->toBeNull()
        ->and($createdApp->tenant_id)->toBe($tenantA->id)
        ->and($createdApp->tenant_id)->not->toBe($tenantB->id);
});

test('global tenant scope automatically scopes raw Eloquent queries when tenant context is active', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    App::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Direct App A',
        'platform' => 'woo',
        'package_name' => 'com.direct.a',
        'app_key' => 'wia_key_direct_a',
    ]);

    App::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Direct App B',
        'platform' => 'woo',
        'package_name' => 'com.direct.b',
        'app_key' => 'wia_key_direct_b',
    ]);

    // Set Tenant A context
    TenantContext::set($tenantA);

    $resultsForA = App::all();
    expect($resultsForA)->toHaveCount(1)
        ->and($resultsForA->first()->tenant_id)->toBe($tenantA->id);

    // Switch to Tenant B context
    TenantContext::set($tenantB);

    $resultsForB = App::all();
    expect($resultsForB)->toHaveCount(1)
        ->and($resultsForB->first()->tenant_id)->toBe($tenantB->id);

    TenantContext::forget();
});
