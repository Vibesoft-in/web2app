<?php

use App\Models\App;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;

beforeEach(function () {
    TenantContext::forget();
});

test('guest can access customer login page', function () {
    $response = $this->get('/app/login');
    $response->assertSuccessful();
});

test('guest can access customer registration page', function () {
    $response = $this->get('/app/register');
    $response->assertSuccessful();
});

test('dashboard aliases redirect to app panel', function () {
    $this->get('/dashboard')->assertRedirect('/app');
    $this->get('/dashboard/login')->assertRedirect('/app/login');
    $this->get('/dashboard/register')->assertRedirect('/app/register');
});

test('authenticated customer can access app panel', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)->get('/app');
    $response->assertSuccessful();
});

test('customer can access customer apps listing', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $app = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'My Mobile App',
        'platform' => 'woo',
        'package_name' => 'com.example.mobileapp',
        'app_key' => 'wia_test_cust_key',
    ]);

    $response = $this->actingAs($user)->get('/app/customer-apps');
    $response->assertSuccessful();
    $response->assertSee('My Mobile App');
});
