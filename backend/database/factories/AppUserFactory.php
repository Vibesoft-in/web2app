<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AppUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppUser>
 */
class AppUserFactory extends Factory
{
    protected $model = AppUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'phone' => '+91'.fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'store_customer_id' => (string) fake()->numberBetween(100, 9999),
            'name' => fake()->name(),
            'last_login_at' => now(),
        ];
    }
}
