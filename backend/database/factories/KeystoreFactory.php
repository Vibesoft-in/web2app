<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Keystore;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Keystore>
 */
class KeystoreFactory extends Factory
{
    protected $model = Keystore::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'app_id' => App::factory(),
            'path' => 'tenants/1/apps/1/keystore.jks',
            'alias' => 'upload_key',
            'passwords' => [
                'store_password' => Str::random(32),
                'key_password' => Str::random(32),
            ],
            'sha1' => '14:6D:E2:B0:9C:F8:7A:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD',
            'sha256' => '3B:F4:A8:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90',
        ];
    }
}
