<?php

use App\Models\App;
use App\Models\AppUser;
use App\Models\Tenant;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('mobile API rejects requests without X-App-Key header', function () {
    $response = $this->getJson('/v1/app-config');

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_APP_KEY');
});

test('mobile API rejects requests with invalid X-App-Key header', function () {
    $response = $this->withHeaders(['X-App-Key' => 'wia_non_existent_key'])
        ->getJson('/v1/app-config');

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_APP_KEY');
});

test('mobile API rejects requests when tenant is suspended', function () {
    $tenant = Tenant::factory()->create(['status' => 'suspended']);
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_suspended_app_key',
    ]);

    $response = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->getJson('/v1/app-config');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'TENANT_INACTIVE');
});

test('OTP request generates 6-digit hashed OTP with 5-minute expiry and 30s resend cooldown', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $phone = '+919876543210';

    $response = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', [
            'phone' => $phone,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'OTP sent successfully.')
        ->assertJsonPath('expires_in', 300)
        ->assertJsonPath('resend_after', 30);

    expect($response->json('debug_otp'))->not->toBeNull()
        ->and(strlen($response->json('debug_otp')))->toBe(6);

    // Immediate second request within 30 seconds must be rejected by cooldown
    $secondResponse = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', [
            'phone' => $phone,
        ]);

    $secondResponse->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

test('OTP request enforces rate limiting of maximum 3 requests per 10 minutes', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $phone = '+919876543211';
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    $phoneHash = md5($cleanPhone);
    $cooldownKey = "tenant:{$app->tenant_id}:app:{$app->id}:otp:cooldown:{$phoneHash}";

    // Request 1
    $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone])
        ->assertStatus(200);

    // Clear cooldown to simulate waiting 30 seconds
    Cache::forget($cooldownKey);

    // Request 2
    $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone])
        ->assertStatus(200);

    Cache::forget($cooldownKey);

    // Request 3
    $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone])
        ->assertStatus(200);

    Cache::forget($cooldownKey);

    // Request 4 must fail with too many requests error
    $fourthResponse = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone]);

    $fourthResponse->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
    expect($fourthResponse->json('errors.phone.0'))->toContain('Too many OTP requests');
});

test('OTP verification limits invalid attempts to maximum 5 before lockout', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $phone = '+919876543212';

    $otpResp = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone]);
    $realOtp = $otpResp->json('debug_otp');

    // Attempt 1 to 4 with incorrect OTP
    for ($i = 1; $i <= 4; $i++) {
        $failResp = $this->withHeaders(['X-App-Key' => $app->app_key])
            ->postJson('/v1/auth/otp/verify', [
                'phone' => $phone,
                'otp' => '000000',
            ]);
        $failResp->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
        expect($failResp->json('errors.otp.0'))->toContain((string) (5 - $i));
    }

    // Attempt 5 exceeds maximum attempts
    $fifthResp = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => '000000',
        ]);

    $fifthResp->assertStatus(422)
        ->assertJsonValidationErrors(['otp']);
    expect($fifthResp->json('errors.otp.0'))->toContain('Maximum verification attempts exceeded');

    // Attempt 6 even with correct OTP fails because OTP is wiped
    $sixthResp = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => $realOtp,
        ]);

    $sixthResp->assertStatus(422)
        ->assertJsonValidationErrors(['otp']);
    expect($sixthResp->json('errors.otp.0'))->toContain('expired or does not exist');
});

test('successful OTP verification creates user, links store_customer_id, and issues app-scoped token', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $phone = '+919876543213';

    $reqResp = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/request', ['phone' => $phone]);
    $otp = $reqResp->json('debug_otp');

    $verifyResp = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => $otp,
            'name' => 'Alice Customer',
        ]);

    $verifyResp->assertStatus(200);

    $token = $verifyResp->json('data.token');
    $userData = $verifyResp->json('data.user');

    expect($token)->not->toBeEmpty()
        ->and($userData['phone'])->toBe($phone)
        ->and($userData['name'])->toBe('Alice Customer')
        ->and($userData['store_customer_id'])->not->toBeEmpty();

    // Verify token ability in database
    $user = AppUser::where('phone', $phone)->first();
    expect($user)->not->toBeNull();
    $sanctumToken = $user->tokens()->first();
    expect($sanctumToken->abilities)->toContain("app:{$app->id}");
});

test('Google login verifies token, links store_customer_id, and issues app-scoped token', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/auth/social/google', [
            'id_token' => 'mock_google_token_123',
        ]);

    $response->assertStatus(200);

    $token = $response->json('data.token');
    $userData = $response->json('data.user');

    expect($token)->not->toBeEmpty()
        ->and($userData['email'])->toBe('customer.test@example.com')
        ->and($userData['store_customer_id'])->not->toBeEmpty();

    $user = AppUser::where('email', 'customer.test@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->tokens()->first()->abilities)->toContain("app:{$app->id}");
});
