<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test /healthz returns 200 with structured component checks.
     */
    public function test_healthz_endpoint_returns_ok_with_subsystem_checks(): void
    {
        $response = $this->getJson('/healthz');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonPath('checks.storage.status', 'ok');

        $this->assertArrayHasKey('response_time_ms', $response->json());
        $this->assertArrayHasKey('system', $response->json());
    }

    /**
     * Test /api/health also returns 200.
     */
    public function test_api_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }
}
