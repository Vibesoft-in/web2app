<?php

use App\Models\App;
use App\Models\AppUser;
use App\Models\Tenant;

test('token issued for App X is strictly rejected on App Y with 403 APP_MISMATCH', function () {
    $tenant = Tenant::factory()->create();

    $appX = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_x',
        'name' => 'Store App X',
    ]);

    $appY = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_y',
        'name' => 'Store App Y',
    ]);

    // Create user belonging to App X with null store_customer_id
    $userX = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $appX->id,
        'store_customer_id' => null,
    ]);

    // Mint token scoped to App X
    $tokenX = $userX->createToken('mobile-session', ["app:{$appX->id}"])->plainTextToken;

    // 1. Presenting App X token to App X with App X's key succeeds
    auth('sanctum')->forgetUser();
    $allowedResponse = $this->withHeaders([
        'X-App-Key' => $appX->app_key,
        'Authorization' => "Bearer {$tokenX}",
    ])->getJson('/v1/orders');

    $allowedResponse->assertStatus(200);

    // 2. Presenting App X token to App Y with App Y's key is rejected with 403 APP_MISMATCH
    auth('sanctum')->forgetUser();
    $rejectedResponse = $this->withHeaders([
        'X-App-Key' => $appY->app_key,
        'Authorization' => "Bearer {$tokenX}",
    ])->getJson('/v1/orders');

    $rejectedResponse->assertStatus(403)
        ->assertJsonPath('error.code', 'APP_MISMATCH');
    expect($rejectedResponse->json('error.message'))->toContain('not authorized');
});

test('token issued without app scope ability is rejected on mobile endpoints', function () {
    $tenant = Tenant::factory()->create();

    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_test',
    ]);

    $user = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'store_customer_id' => null,
    ]);

    // Token with wrong ability
    $wrongToken = $user->createToken('wrong-scope', ['app:999999'])->plainTextToken;

    auth('sanctum')->forgetUser();
    $response = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$wrongToken}",
    ])->getJson('/v1/orders');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'APP_MISMATCH');
});
