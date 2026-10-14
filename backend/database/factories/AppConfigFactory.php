<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AppConfig;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppConfigFactory extends Factory
{
    protected $model = AppConfig::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'version' => 1,
            'state' => 'published',
            'config' => [
                'theme' => [
                    'primary_color' => '#6366f1',
                    'app_name' => 'Store App',
                ],
                'app' => [
                    'name' => 'Store App',
                    'min_version' => '1.0.0',
                ],
            ],
            'published_at' => now(),
        ];
    }
}
