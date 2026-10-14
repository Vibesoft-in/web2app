<?php

use App\Enums\BuildStatus;
use App\Models\App;
use App\Models\Build;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('build transitions through valid lifecycle states from queued to ready', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::QUEUED,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    expect($build->status)->toBe(BuildStatus::QUEUED);

    // queued -> preparing
    $build->transitionTo(BuildStatus::PREPARING);
    expect($build->fresh()->status)->toBe(BuildStatus::PREPARING);

    // preparing -> building
    $build->transitionTo(BuildStatus::BUILDING);
    expect($build->fresh()->status)->toBe(BuildStatus::BUILDING);

    // building -> signing
    $build->transitionTo(BuildStatus::SIGNING);
    expect($build->fresh()->status)->toBe(BuildStatus::SIGNING);

    // signing -> ready
    $build->transitionTo(BuildStatus::READY);
    expect($build->fresh()->status)->toBe(BuildStatus::READY)
        ->and($build->status->isTerminal())->toBeTrue();
});

test('any active build state can transition to failed with error message', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::BUILDING,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    $build->transitionTo(BuildStatus::FAILED, 'Gradle compilation timeout.');

    $fresh = $build->fresh();
    expect($fresh->status)->toBe(BuildStatus::FAILED)
        ->and($fresh->error)->toBe('Gradle compilation timeout.')
        ->and($fresh->status->isTerminal())->toBeTrue();
});

test('invalid state transition is strictly rejected with exception', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::QUEUED,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    // Illegal skip from queued directly to ready
    expect(fn () => $build->transitionTo(BuildStatus::READY))
        ->toThrow(InvalidArgumentException::class);
});

test('terminal state cannot transition to any other state', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $build = Build::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'status' => BuildStatus::READY,
        'config_version' => 1,
        'version_code' => 1,
        'version_name' => '1.0.0',
    ]);

    expect(fn () => $build->transitionTo(BuildStatus::BUILDING))
        ->toThrow(InvalidArgumentException::class);
});
