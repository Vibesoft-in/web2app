<?php

use App\Exceptions\EntitlementException;
use App\Models\App;
use App\Models\Build;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Services\Billing\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->entitlements = new EntitlementService();
});

test('suspended tenant has zero entitlements', function () {
    $tenant = Tenant::factory()->create(['status' => 'suspended']);
    $plan = Plan::factory()->create();
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    expect($this->entitlements->can($tenant, 'whatsapp'))->toBeFalse()
        ->and($this->entitlements->can($tenant, 'build'))->toBeFalse();
});

test('trial tenant has limited single app and build entitlement', function () {
    $tenant = Tenant::factory()->create(['status' => 'trial']);

    expect($this->entitlements->can($tenant, 'build'))->toBeTrue()
        ->and($this->entitlements->can($tenant, 'apps'))->toBeTrue()
        ->and($this->entitlements->can($tenant, 'whatsapp'))->toBeFalse()
        ->and($this->entitlements->can($tenant, 'push'))->toBeFalse();
});

test('feature flags and limits are respected according to plan definitions', function () {
    $tenant = Tenant::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create([
        'features' => [
            'push_notifications' => true,
            'whatsapp' => false,
        ],
        'limits' => [
            'builds_per_month' => 2,
            'push_per_month' => 100,
            'max_apps' => 3,
        ],
    ]);

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(20),
    ]);

    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    expect($this->entitlements->can($tenant, 'whatsapp'))->toBeFalse()
        ->and($this->entitlements->can($tenant, 'push'))->toBeTrue()
        ->and($this->entitlements->can($tenant, 'build'))->toBeTrue();

    // Create 2 builds -> hits limit
    Build::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'created_at' => now(),
    ]);

    expect($this->entitlements->can($tenant, 'build'))->toBeFalse();

    // Record 100 push notifications -> hits limit
    UsageEvent::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'type' => 'push',
        'qty' => 100,
        'cost' => 0.05,
        'created_at' => now(),
    ]);

    expect($this->entitlements->can($tenant, 'push'))->toBeFalse();
});

test('grace period keeps features unlocked within 7 days and locks features after 7 days', function () {
    $tenant = Tenant::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create([
        'features' => ['whatsapp' => true],
        'limits' => ['builds_per_month' => 5],
    ]);

    // Case A: Expired 3 days ago -> inside 7 days grace period
    $sub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_end' => now()->subDays(3),
    ]);

    expect($this->entitlements->isInGracePeriod($tenant))->toBeTrue()
        ->and($this->entitlements->isFeatureLocked($tenant))->toBeFalse()
        ->and($this->entitlements->can($tenant, 'whatsapp'))->toBeTrue();

    // Case B: Expired 8 days ago -> beyond 7 days grace period -> feature lock!
    $sub->update([
        'current_period_end' => now()->subDays(8),
    ]);

    expect($this->entitlements->isInGracePeriod($tenant))->toBeFalse()
        ->and($this->entitlements->isFeatureLocked($tenant))->toBeTrue()
        ->and($this->entitlements->can($tenant, 'whatsapp'))->toBeFalse()
        ->and($this->entitlements->can($tenant, 'build'))->toBeFalse();

    // assertCan throws EntitlementException with FEATURE_LOCKED
    expect(fn () => $this->entitlements->assertCan($tenant, 'build'))
        ->toThrow(EntitlementException::class, 'Subscription has expired and grace period has ended');
});
