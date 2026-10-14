<?php

namespace Tests\Feature;

use App\Jobs\SendPushBatchJob;
use App\Models\App;
use App\Models\AppUser;
use App\Models\PushDevice;
use App\Models\Tenant;
use App\Services\Billing\UsageEventService;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TenantAndUserCacheIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test 1: Two tenants in parallel requesting OTP for the same phone number.
     * Caches must NOT collide, cooldowns must be isolated, and OTPs must not be cross-valid.
     */
    public function test_two_tenants_parallel_otp_cache_isolation(): void
    {
        // Tenant 1
        $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        $app1 = App::create([
            'tenant_id' => $tenant1->id,
            'name' => 'App 1',
            'package_name' => 'com.tenant1.app',
            'app_key' => 'ak_tenant_1_key',
            'platform' => 'woo',
            'status' => 'live',
        ]);

        // Tenant 2
        $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 'tenant-2']);
        $app2 = App::create([
            'tenant_id' => $tenant2->id,
            'name' => 'App 2',
            'package_name' => 'com.tenant2.app',
            'app_key' => 'ak_tenant_2_key',
            'platform' => 'woo',
            'status' => 'live',
        ]);

        $sharedPhone = '+919988776655';

        // 1. Tenant 1 requests OTP
        $res1 = $this->withHeaders(['X-App-Key' => $app1->app_key])
            ->postJson('/v1/auth/otp/request', ['phone' => $sharedPhone]);
        $res1->assertStatus(200);
        $otp1 = $res1->json('debug_otp');

        // 2. Tenant 2 requests OTP for same phone in parallel (must NOT be blocked by Tenant 1's cooldown)
        $res2 = $this->withHeaders(['X-App-Key' => $app2->app_key])
            ->postJson('/v1/auth/otp/request', ['phone' => $sharedPhone]);
        $res2->assertStatus(200);
        $otp2 = $res2->json('debug_otp');

        $this->assertNotEmpty($otp1);
        $this->assertNotEmpty($otp2);

        // 3. Attempt to use Tenant 1's OTP on Tenant 2: must be rejected!
        $tamperRes = $this->withHeaders(['X-App-Key' => $app2->app_key])
            ->postJson('/v1/auth/otp/verify', [
                'phone' => $sharedPhone,
                'otp' => $otp1,
            ]);

        // Even if digits accidentally match (1 in 1M), if distinct, must be invalid
        if ($otp1 !== $otp2) {
            $tamperRes->assertStatus(422);
            $tamperRes->assertJsonValidationErrors(['otp']);
        }

        // 4. Tenant 2 successfully verifies its own OTP
        $validRes = $this->withHeaders(['X-App-Key' => $app2->app_key])
            ->postJson('/v1/auth/otp/verify', [
                'phone' => $sharedPhone,
                'otp' => $otp2,
            ]);
        $validRes->assertStatus(200);

        // 5. Tenant 1 can still verify its own OTP independently
        $validRes1 = $this->withHeaders(['X-App-Key' => $app1->app_key])
            ->postJson('/v1/auth/otp/verify', [
                'phone' => $sharedPhone,
                'otp' => $otp1,
            ]);
        $validRes1->assertStatus(200);
    }

    /**
     * Test 2: Two customers on the SAME device (user switch scenario).
     * Device tokens must be cleanly disassociated on logout and reassigned to Customer B.
     */
    public function test_user_switch_on_same_device_clears_personal_push_binding(): void
    {
        $tenant = Tenant::create(['name' => 'Switch Tenant', 'slug' => 'switch-tenant']);
        $app = App::create([
            'tenant_id' => $tenant->id,
            'name' => 'Switch App',
            'package_name' => 'com.switch.app',
            'app_key' => 'ak_switch_app_key',
            'platform' => 'woo',
            'status' => 'live',
        ]);

        $deviceToken = 'shared_physical_fcm_token_12345';

        // 1. Customer A logs in
        $userA = AppUser::create([
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'phone' => '+919111111111',
            'name' => 'Customer Alice',
            'store_customer_id' => '1001',
        ]);
        $tokenA = $userA->createToken('session-a', ["app:{$app->id}"])->plainTextToken;

        // Customer A registers the device
        $this->withHeaders([
            'X-App-Key' => $app->app_key,
            'Authorization' => "Bearer {$tokenA}",
        ])->postJson('/v1/devices', [
            'fcm_token' => $deviceToken,
            'platform' => 'android',
        ])->assertStatus(200);

        $pushDevice = PushDevice::where('app_id', $app->id)
            ->where('fcm_token', $deviceToken)
            ->first();
        $this->assertNotNull($pushDevice);
        $this->assertEquals($userA->id, $pushDevice->app_user_id);

        // 2. Customer A logs out
        $this->withHeaders([
            'X-App-Key' => $app->app_key,
            'Authorization' => "Bearer {$tokenA}",
        ])->postJson('/v1/auth/logout', [
            'fcm_token' => $deviceToken,
        ])->assertStatus(200);

        // After logout: Customer A's session token is revoked
        auth('sanctum')->forgetUser();
        $this->withHeaders([
            'X-App-Key' => $app->app_key,
            'Authorization' => "Bearer {$tokenA}",
        ])->getJson('/v1/orders')->assertStatus(401);

        // After logout: Device is either deleted or no longer tied to Customer A
        $deviceAfterLogout = PushDevice::where('app_id', $app->id)
            ->where('fcm_token', $deviceToken)
            ->first();
        $this->assertTrue($deviceAfterLogout === null || $deviceAfterLogout->app_user_id === null);

        // 3. Customer B logs in on the SAME device
        $userB = AppUser::create([
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'phone' => '+919222222222',
            'name' => 'Customer Bob',
            'store_customer_id' => '1002',
        ]);
        $tokenB = $userB->createToken('session-b', ["app:{$app->id}"])->plainTextToken;

        // Customer B registers the same device token
        $this->withHeaders([
            'X-App-Key' => $app->app_key,
            'Authorization' => "Bearer {$tokenB}",
        ])->postJson('/v1/devices', [
            'fcm_token' => $deviceToken,
            'platform' => 'android',
        ])->assertStatus(200);

        $deviceForB = PushDevice::where('app_id', $app->id)
            ->where('fcm_token', $deviceToken)
            ->first();
        $this->assertNotNull($deviceForB);
        $this->assertEquals($userB->id, $deviceForB->app_user_id);
    }

    /**
     * Test 3: Push notification token cleanup in background jobs is strictly tenant-scoped.
     */
    public function test_push_batch_cleanup_is_strictly_scoped_by_tenant_and_app(): void
    {
        $tenant1 = Tenant::create(['name' => 'Tenant One', 'slug' => 't-one']);
        $app1 = App::create([
            'tenant_id' => $tenant1->id,
            'name' => 'App 1',
            'package_name' => 'com.t1.app',
            'app_key' => 'ak_t1',
            'status' => 'live',
        ]);

        $tenant2 = Tenant::create(['name' => 'Tenant Two', 'slug' => 't-two']);
        $app2 = App::create([
            'tenant_id' => $tenant2->id,
            'name' => 'App 2',
            'package_name' => 'com.t2.app',
            'app_key' => 'ak_t2',
            'status' => 'live',
        ]);

        $badToken = 'dead_fcm_token_999';

        // Device registered under Tenant 1
        PushDevice::create([
            'tenant_id' => $tenant1->id,
            'app_id' => $app1->id,
            'fcm_token' => $badToken,
            'platform' => 'android',
        ]);

        // Device registered under Tenant 2 with same string
        PushDevice::create([
            'tenant_id' => $tenant2->id,
            'app_id' => $app2->id,
            'fcm_token' => $badToken,
            'platform' => 'android',
        ]);

        $fcmMock = Mockery::mock(FcmServiceInterface::class);
        $fcmMock->shouldReceive('sendBatch')
            ->once()
            ->andReturn([
                'sent' => 0,
                'failed' => 1,
                'invalid_tokens' => [$badToken],
            ]);

        $job = new SendPushBatchJob(
            appId: $app1->id,
            tokens: [$badToken],
            title: 'Test',
            body: 'Hello'
        );

        $job->handle($fcmMock, app(UsageEventService::class));

        // Tenant 1's device token should be cleaned up
        $this->assertDatabaseMissing('push_devices', [
            'app_id' => $app1->id,
            'fcm_token' => $badToken,
        ]);

        // Tenant 2's device token MUST STILL EXIST (not deleted by Tenant 1's job)
        $this->assertDatabaseHas('push_devices', [
            'app_id' => $app2->id,
            'fcm_token' => $badToken,
        ]);
    }
}
