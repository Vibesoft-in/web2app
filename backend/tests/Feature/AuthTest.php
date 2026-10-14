<?php

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a user can register a new tenant and owner account', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Alice Admin',
        'email' => 'alice@example.com',
        'password' => 'Password123!',
        'company_name' => 'Acme E-commerce',
        'phone' => '+919876543210',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'tenant_id'],
                'tenant' => ['id', 'ulid', 'name', 'status'],
                'token',
            ],
        ]);

    $this->assertDatabaseHas('tenants', [
        'name' => 'Acme E-commerce',
        'status' => 'trial',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'alice@example.com',
        'role' => 'owner',
    ]);
});

test('registration requires strong password of at least 10 characters with numbers and letters', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'password' => 'short1',
        'company_name' => 'Bob Store',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration rejects duplicate emails', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'existing@example.com',
    ]);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Duplicate User',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'company_name' => 'Duplicate Store',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('a user can log in with valid credentials', function () {
    $tenant = Tenant::factory()->create(['status' => 'trial']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['user', 'tenant', 'token'],
        ]);
});

test('login fails with invalid credentials and hits rate limiter', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);
        $response->assertStatus(422);
    }

    // 6th attempt should be locked out
    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'WrongPassword!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('cannot log in if tenant account is suspended', function () {
    $tenant = Tenant::factory()->create(['status' => 'suspended']);
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'suspended@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'suspended@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'TENANT_INACTIVE');
});

test('authenticated user can view their profile via me endpoint', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($user, ['tenant:'.$tenant->id]);

    $response = $this->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.tenant.id', $tenant->id);
});

test('authenticated user can log out and revoke their token', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $token = $user->createToken('test_token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(0);
});

test('user can verify their email address', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    $hash = sha1($user->getEmailForVerification());

    $response = $this->getJson("/api/auth/verify-email/{$user->id}/{$hash}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Email verified successfully.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
