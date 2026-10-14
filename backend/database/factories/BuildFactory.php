<?php

namespace Database\Factories;

use App\Enums\BuildStatus;
use App\Models\App;
use App\Models\Build;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Build>
 */
class BuildFactory extends Factory
{
    protected $model = Build::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'ulid' => (string) Str::ulid(),
            'config_version' => 1,
            'status' => BuildStatus::QUEUED,
            'version_code' => 1,
            'version_name' => '1.0.0',
        ];
    }
}
