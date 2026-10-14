<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\ImpersonationService;

beforeEach(function () {
    $this->ownerTenant = Tenant::factory()->create(['status' => 'active']);
    $this->ownerUser = User::factory()->create([
        'tenant_id' => $this->ownerTenant->id,
        'email' => 'owner@webintoapp.com',
        'role' => 'owner',
    ]);

    $this->clientTenant = Tenant::factory()->create(['status' => 'active']);
    $this->clientUser = User::factory()->create([
        'tenant_id' => $this->clientTenant->id,
        'email' => 'client@clientstore.com',
        'role' => 'viewer',
    ]);
});

test('viewer or non-owner user cannot access filament admin panel', function () {
    $response = $this->actingAs($this->clientUser)
        ->get('/admin');

    // Filament returns 403 Forbidden when canAccessPanel is false
    $response->assertStatus(403);
});

test('owner user can access filament admin panel dashboard', function () {
    $response = $this->actingAs($this->ownerUser)
        ->get('/admin');

    $response->assertStatus(200);
});

test('impersonation starts with audit trail, switches user, and can be cleanly ended', function () {
    /** @var ImpersonationService $service */
    $service = app(ImpersonationService::class);

    // 1. Authenticate as owner
    $this->actingAs($this->ownerUser);

    // 2. Start impersonation of client user
    $service->start($this->ownerUser, $this->clientUser);

    expect(auth()->id())->toBe($this->clientUser->id)
        ->and($service->isImpersonating())->toBeTrue()
        ->and($service->getImpersonator()->id)->toBe($this->ownerUser->id);

    // 3. Verify audit log entry for impersonation start
    $startLog = AuditLog::where('action', 'impersonation_start')->latest('id')->first();
    expect($startLog)->not->toBeNull()
        ->and($startLog->actor_id)->toBe($this->ownerUser->id)
        ->and($startLog->tenant_id)->toBe($this->clientTenant->id);

    // 4. Leaving impersonation via route
    $response = $this->actingAs($this->clientUser)
        ->withSession([ImpersonationService::SESSION_KEY => $this->ownerUser->id])
        ->get('/admin/impersonate/leave');
    $response->assertRedirect('/admin');

    // 5. Restored original owner session
    expect(auth()->id())->toBe($this->ownerUser->id)
        ->and($service->isImpersonating())->toBeFalse();

    // 6. Verify audit log entry for impersonation leave
    $leaveLog = AuditLog::withoutGlobalScopes()->where('action', 'impersonation_leave')->latest('id')->first();
    expect($leaveLog)->not->toBeNull()
        ->and($leaveLog->actor_id)->toBe($this->ownerUser->id);
});
