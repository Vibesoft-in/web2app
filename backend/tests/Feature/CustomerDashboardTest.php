<?php

use App\Filament\CustomerResources\CustomerAppResource\Pages\CreateCustomerApp;
use App\Models\App;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Livewire\Livewire;

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

test('customer can access app creation wizard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)->get('/app/customer-apps/create');
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

test('admin owner can access admin panel and its resource pages without redirecting to dashboard', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'owner@webintoapp.com',
        'role' => 'owner',
    ]);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/apps')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/tenants')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/subscriptions')->assertSuccessful();
});

test('app creation validates package name format and uniqueness', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Existing App',
        'platform' => 'woo',
        'package_name' => 'com.existing.app',
        'app_key' => 'wia_test_exist',
    ]);

    $this->actingAs($user);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    Livewire::test(CreateCustomerApp::class)
        ->fillForm([
            'name' => 'Test App',
            'package_name' => 'RISHABHTYAGI162@GMAIL.COM',
            'platform' => 'woo',
        ])
        ->call('create')
        ->assertHasFormErrors(['package_name' => 'regex']);

    Livewire::test(CreateCustomerApp::class)
        ->fillForm([
            'name' => 'Test App 2',
            'package_name' => 'com.existing.app',
            'platform' => 'woo',
        ])
        ->call('create')
        ->assertHasFormErrors(['package_name' => 'unique']);
});
