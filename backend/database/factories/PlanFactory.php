<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Pro Plan',
            'slug' => fake()->unique()->slug(),
            'price_monthly' => 2999.00,
            'features' => ['builds', 'push_notifications', 'whatsapp'],
            'limits' => [
                'builds_per_month' => 10,
                'push_per_month' => 50000,
            ],
            'is_active' => true,
        ];
    }
}
