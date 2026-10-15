<?php

use App\Enums\BuildStatus;
use App\Filament\CustomerResources\CustomerAppResource\Pages\CreateCustomerApp;
use App\Filament\CustomerResources\CustomerAppResource\Pages\ListCustomerApps;
use App\Filament\CustomerResources\CustomerBuildResource\Pages\ListCustomerBuilds;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Models\App;
use App\Models\Build;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

test('guest can register a new account and is redirected to app dashboard', function () {
    filament()->setCurrentPanel(filament()->getPanel('app'));

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Alice Customer',
            'email' => 'alice@customer.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app');

    $user = User::where('email', 'alice@customer.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->tenant_id)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('guest registration safely redirects to app even if session has stale admin intended URL', function () {
    filament()->setCurrentPanel(filament()->getPanel('app'));

    session()->put('url.intended', 'http://localhost/admin');

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Stale Intended User',
            'email' => 'stale@customer.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app');

    expect(session()->has('url.intended'))->toBeFalse();
});

test('customer login safely redirects to app even if session has stale admin intended URL', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
        'password' => Hash::make('Password123!'),
    ]);

    filament()->setCurrentPanel(filament()->getPanel('app'));
    session()->put('url.intended', 'http://localhost/admin');

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'Password123!',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app');
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

test('app creation wizard automatically provisions verified connection, published config, and trial subscription', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $this->actingAs($user);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    Livewire::test(CreateCustomerApp::class)
        ->fillForm([
            'name' => 'Boutique Apparel',
            'package_name' => 'com.boutique.apparel',
            'platform' => 'woo',
            'store_url' => 'https://boutique.example.com',
            'theme_preset' => 'boutique',
            'primary_color' => '#f43f5e',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $app = App::where('package_name', 'com.boutique.apparel')->first();
    expect($app)->not->toBeNull()
        ->and($app->connection)->not->toBeNull()
        ->and($app->connection->status)->toBe('verified')
        ->and($app->configs()->where('state', 'published')->count())->toBe(1)
        ->and($tenant->fresh()->hasActiveSubscription($app->id))->toBeTrue();
});

test('customer can preview app with interactive phone frame simulator', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $app = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Apex Luxury Wear',
        'platform' => 'woo',
        'package_name' => 'com.apex.luxury',
        'app_key' => 'wia_apex_preview_key',
    ]);

    $this->actingAs($user);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    $view = view('filament.customer.phone-preview', [
        'app' => $app,
        'config' => [
            'theme' => [
                'preset' => 'apex',
                'primary_color' => '#10b981',
            ],
        ],
    ])->render();

    expect($view)->toContain('Apex Luxury Wear')
        ->toContain('com.apex.luxury')
        ->toContain('Live App Simulator')
        ->toContain('Minimalist')
        ->toContain('Boutique')
        ->toContain('Apex Dark')
        ->toContain('Featured Products');
});

test('customer can compile and download APK directly from dashboard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $app = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Quick Commerce App',
        'platform' => 'woo',
        'package_name' => 'com.quick.commerce',
        'app_key' => 'wia_quick_apk_key',
    ]);

    $this->actingAs($user);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    // Test build_apk action via ListCustomerApps
    Livewire::test(ListCustomerApps::class)
        ->callTableAction('build_apk', $app);

    $latestBuild = $app->fresh()->builds()->latest()->first();
    expect($latestBuild)->not->toBeNull()
        ->and($latestBuild->status)->toBe(BuildStatus::READY)
        ->and($latestBuild->artifact_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($latestBuild->artifact_path))->toBeTrue();

    // Verify artifact is an installable APK
    expect($latestBuild->artifact_path)->toContain('app-release.apk');

    // Test download_apk action
    $downloadResponse = Livewire::test(ListCustomerApps::class)
        ->callTableAction('download_apk', $app);

    $downloadResponse->assertFileDownloaded('com.quick.commerce-v1.0.0-b1.apk');
});

test('customer can view builds list and download APK and AAB from builds page', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $app = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mega Mart App',
        'platform' => 'woo',
        'package_name' => 'com.megamart.app',
        'app_key' => 'wia_megamart_key',
    ]);

    $ulid = '01HZXTESTBUILDULID00000000';
    $apkPath = "tenants/{$tenant->id}/apps/{$app->id}/builds/{$ulid}/app-release.apk";
    $aabPath = "tenants/{$tenant->id}/apps/{$app->id}/builds/{$ulid}/app-release.aab";
    Storage::disk('local')->put($apkPath, 'MOCK_APK_BINARY');
    Storage::disk('local')->put($aabPath, 'MOCK_AAB_BINARY');

    $build = Build::create([
        'ulid' => $ulid,
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::READY,
        'version_code' => 5,
        'version_name' => '2.1.0',
        'artifact_path' => $apkPath,
    ]);

    $this->actingAs($user);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    // Test access to builds resource
    $response = $this->get('/app/customer-builds');
    $response->assertSuccessful();
    $response->assertSee('Mega Mart App');
    $response->assertSee('2.1.0');

    // Test download_apk on ListCustomerBuilds
    $downloadApk = Livewire::test(ListCustomerBuilds::class)
        ->callTableAction('download_apk', $build);

    $downloadApk->assertFileDownloaded('com.megamart.app-v2.1.0-b5.apk');

    // Test download_aab on ListCustomerBuilds
    $downloadAab = Livewire::test(ListCustomerBuilds::class)
        ->callTableAction('download_aab', $build);

    $downloadAab->assertFileDownloaded('com.megamart.app-v2.1.0-b5.aab');
});

test('customer dashboard renders actual dynamic tenant data without hardcoding', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Merchant',
        'role' => 'owner',
    ]);

    $app1 = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Boutique',
        'platform' => 'woo',
        'package_name' => 'com.jane.boutique',
        'app_key' => 'wia_jane_key_1',
    ]);

    $app2 = App::create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Electronics',
        'platform' => 'shopify',
        'package_name' => 'com.jane.electronics',
        'app_key' => 'wia_jane_key_2',
    ]);

    Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app1->id,
        'status' => BuildStatus::READY,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app2->id,
        'status' => BuildStatus::BUILDING,
        'version_code' => 2,
        'version_name' => '1.0.0',
    ]);

    $response = $this->actingAs($user)->get('/app');
    $response->assertSuccessful();

    // Verify dynamic welcome banner
    $response->assertSee('Welcome back, Jane Merchant');
    $response->assertSee('J'); // User Initial

    // Verify dynamic apps list and counts
    $response->assertSee('Jane Boutique');
    $response->assertSee('com.jane.boutique');
    $response->assertSee('Jane Electronics');
    $response->assertSee('com.jane.electronics');

    // Verify it does NOT contain hardcoded sample data from mockup
    $response->assertDontSee('QWRT Store');
    $response->assertDontSee('com.qwrt.store');
    $response->assertDontSee('Fashion Hub');
    $response->assertDontSee('com.fashion.hub');
});
