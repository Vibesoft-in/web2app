<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Connection;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    protected $model = Connection::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'platform' => 'woo',
            'site_url' => 'https://mystore.example.com',
            'credentials' => [
                'consumer_key' => 'ck_test_123',
                'consumer_secret' => 'cs_test_456',
            ],
            'status' => 'verified',
            'last_verified_at' => now(),
        ];
    }
}
