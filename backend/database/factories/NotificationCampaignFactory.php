<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\NotificationCampaign;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationCampaignFactory extends Factory
{
    protected $model = NotificationCampaign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(8),
            'deep_link' => 'product:42',
            'audience' => ['target' => 'all'],
            'scheduled_at' => null,
            'status' => 'draft',
            'sent_count' => 0,
            'failed_count' => 0,
        ];
    }
}
