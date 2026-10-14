<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    protected $model = App::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ulid' => (string) Str::ulid(),
            'name' => fake()->words(2, true),
            'platform' => 'woo',
            'package_name' => 'com.'.fake()->unique()->slug(2).'.app',
            'app_key' => 'wia_'.Str::random(32),
            'setup_step' => 'platform',
            'status' => 'draft',
        ];
    }
}
