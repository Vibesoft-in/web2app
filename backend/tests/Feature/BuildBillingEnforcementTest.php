<?php

use App\Enums\SetupStep;
use App\Jobs\RunBuild;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\Build;
use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['status' => 'active']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'owner',
    ]);
    $this->mobileApp = App::factory()->create([
        'tenant_id' => $this->tenant->id,
        'setup_step' => SetupStep::BUILD->value,
        'status' => 'paid',
    ]);

    Connection::factory()->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'status' => 'verified',
    ]);

    AppConfig::factory()->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'version' => 1,
        'state' => 'published',
    ]);

    $this->plan = Plan::factory()->create([
        'limits' => ['builds_per_month' => 2],
    ]);
});

test('build trigger without payment or active subscription is strictly rejected with 403', function () {
    // No subscription exists
    $response = $this->actingAs($this->user)
        ->postJson("/api/apps/{$this->mobileApp->ulid}/builds");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
});

test('build succeeds once payment is completed and active subscription exists', function () {
    Queue::fake([RunBuild::class]);

    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/apps/{$this->mobileApp->ulid}/builds");

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'queued');

    Queue::assertPushed(RunBuild::class);
});

test('build is blocked when tenant exceeds plan monthly build limit', function () {
    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'plan_id' => $this->plan->id, // limit: 2 builds/month
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    // Create 2 existing builds in current period
    Build::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/apps/{$this->mobileApp->ulid}/builds");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
});

test('build is blocked when subscription expired beyond 7 days grace period', function () {
    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'app_id' => $this->mobileApp->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'current_period_end' => now()->subDays(10), // 10 days ago (past 7 days grace)
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/apps/{$this->mobileApp->ulid}/builds");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'BUILD_NOT_ALLOWED');
});
