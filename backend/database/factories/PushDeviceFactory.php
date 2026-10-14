<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\PushDevice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PushDeviceFactory extends Factory
{
    protected $model = PushDevice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'app_user_id' => null,
            'fcm_token' => 'fcm_token_'.Str::random(32),
            'platform' => 'android',
        ];
    }
}
