<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use App\Models\App;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateCustomerApp extends CreateRecord
{
    protected static string $resource = CustomerAppResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getRedirectUrl(): string
    {
        return CustomerAppResource::getUrl('index', panel: 'app');
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Extract base App fillable attributes
        $appAttributes = collect($data)->only([
            'name',
            'platform',
            'package_name',
            'status',
            'setup_step',
        ])->all();

        try {
            /** @var App $record */
            $record = static::getModel()::create($appAttributes);

            // 1. Setup store connection
            $siteUrl = ! empty($data['store_url']) ? $data['store_url'] : 'https://example.com';
            $record->connection()->create([
                'tenant_id' => $record->tenant_id,
                'platform' => $record->platform,
                'site_url' => $siteUrl,
                'consumer_key' => $data['consumer_key'] ?? 'ck_live_store',
                'consumer_secret' => $data['consumer_secret'] ?? 'cs_live_store',
                'status' => 'verified',
            ]);

            // 2. Setup initial published configuration
            $logoUrl = ! empty($data['logo_url']) ? $data['logo_url'] : (! empty($data['logo_image']) ? '/storage/'.$data['logo_image'] : null);
            $record->configs()->create([
                'tenant_id' => $record->tenant_id,
                'version' => 1,
                'state' => 'published',
                'config' => [
                    'theme' => [
                        'preset' => $data['theme_preset'] ?? 'minimalist',
                        'primary_color' => $data['primary_color'] ?? '#6366f1',
                        'secondary_color' => $data['secondary_color'] ?? '#10b981',
                        'logo_url' => $logoUrl,
                        'promo_banner_text' => $data['promo_banner_text'] ?? '30% OFF Your First Mobile Order',
                    ],
                    'app' => [
                        'name' => $record->name,
                        'package_name' => $record->package_name,
                        'min_version' => '1.0.0',
                    ],
                ],
            ]);

            // 3. Ensure active subscription for building
            $tenant = $record->tenant;
            if ($tenant && ! $tenant->hasActiveSubscription($record->id)) {
                $plan = Plan::firstOrCreate(
                    ['slug' => 'starter'],
                    [
                        'name' => 'Starter Plan',
                        'price_monthly' => 0.00,
                        'features' => ['push_notifications' => true, 'whatsapp' => true],
                        'limits' => ['builds_per_month' => 10, 'max_apps' => 5],
                        'is_active' => true,
                    ]
                );

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'app_id' => $record->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'current_period_end' => now()->addDays(30),
                ]);
            }

            return $record;
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'package_name' => 'This Package ID ('.($data['package_name'] ?? '').') is already registered. Please choose a unique package name (e.g. com.yourbrand.app).',
            ]);
        }
    }
}
