<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsappWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappWalletFactory extends Factory
{
    protected $model = WhatsappWallet::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'balance' => 50.00,
        ];
    }
}
