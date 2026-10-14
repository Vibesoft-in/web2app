<?php

use App\Models\App;
use App\Models\AppUser;
use App\Models\PushDevice;
use App\Models\Tenant;

test('account deletion deletes all tokens, devices, and soft-deletes user', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_delete',
    ]);

    $user = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
    ]);

    $token = $user->createToken('session', ["app:{$app->id}"])->plainTextToken;

    // Register a push device
    $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->postJson('/v1/devices', [
        'fcm_token' => 'fcm_sample_token_xyz',
        'platform' => 'android',
    ])->assertStatus(200);

    expect(PushDevice::where('app_user_id', $user->id)->count())->toBe(1);

    // Call DELETE /v1/account
    $response = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->deleteJson('/v1/account');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Account and personal data successfully deleted.');

    // User record is soft deleted
    expect(AppUser::find($user->id))->toBeNull()
        ->and(AppUser::withTrashed()->find($user->id))->not->toBeNull()
        ->and(PushDevice::where('app_user_id', $user->id)->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0);

    // Subsequent requests using that revoked token return 401
    auth('sanctum')->forgetUser();
    $unauthResp = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->getJson('/v1/orders');

    $unauthResp->assertStatus(401);
});

test('logout revokes current token and unregisters FCM device token', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_logout',
    ]);

    $user = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
    ]);

    $token = $user->createToken('session', ["app:{$app->id}"])->plainTextToken;

    // Register push device
    $device = PushDevice::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'app_user_id' => $user->id,
        'fcm_token' => 'fcm_logout_token_abc',
        'platform' => 'android',
    ]);

    // Unregister push device on logout
    $unregResp = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->deleteJson('/v1/devices', [
        'fcm_token' => 'fcm_logout_token_abc',
    ]);

    $unregResp->assertStatus(200);
    expect(PushDevice::where('fcm_token', 'fcm_logout_token_abc')->count())->toBe(0);

    // Logout endpoint
    $logoutResp = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->postJson('/v1/auth/logout');

    $logoutResp->assertStatus(200);

    // Token is revoked
    auth('sanctum')->forgetUser();
    $postLogout = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$token}",
    ])->getJson('/v1/orders');

    $postLogout->assertStatus(401);
});
