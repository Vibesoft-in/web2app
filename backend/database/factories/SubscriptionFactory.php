<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'plan_id' => Plan::factory(),
            'status' => 'active',
            'razorpay_sub_id' => 'sub_'.fake()->lexify('????????????'),
            'current_period_end' => now()->addMonth(),
        ];
    }
}
