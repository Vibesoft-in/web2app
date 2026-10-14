<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Tenant;
use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WhatsappMessageFactory extends Factory
{
    protected $model = WhatsappMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'phone' => '919876543210',
            'template_name' => 'test_greeting',
            'status' => 'sent',
            'cost' => 0.8000,
            'whatsapp_message_id' => 'wamid.'.Str::random(24),
            'meta' => null,
        ];
    }
}
