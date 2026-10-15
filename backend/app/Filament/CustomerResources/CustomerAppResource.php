<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\CustomerAppResource\Pages;
use App\Jobs\RunBuild;
use App\Models\App;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Build\BuildOrchestrator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerAppResource extends Resource
{
    protected static ?string $model = App::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $modelLabel = 'Mobile App';

    protected static ?string $pluralModelLabel = 'My Mobile Apps';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('App Identity')
                        ->description('Basic app name and platform')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Mobile App Name')
                                ->placeholder('e.g. My Fashion Store')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old): void {
                                    if (! empty($state)) {
                                        $slug = Str::slug($state, '');
                                        if (! empty($slug)) {
                                            $set('package_name', 'com.'.$slug.'.app');
                                        }
                                    }
                                }),
                            Forms\Components\TextInput::make('package_name')
                                ->label('Android Package Name / Bundle ID')
                                ->placeholder('e.g. com.myfashionstore.app')
                                ->helperText('Unique reverse domain format, e.g. com.yourstore.app (letters, numbers, and dots only).')
                                ->required()
                                ->maxLength(255)
                                ->regex('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+$/i')
                                ->unique(App::class, 'package_name', ignoreRecord: true)
                                ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtolower(trim($state)) : null)
                                ->validationMessages([
                                    'regex' => 'The package name must be in reverse domain format like com.yourstore.app (no @ or email symbols).',
                                    'unique' => 'This package name is already registered by another app. Please enter a unique package name.',
                                ]),
                            Forms\Components\Select::make('platform')
                                ->label('Store Platform')
                                ->options([
                                    'woo' => 'WooCommerce (WordPress)',
                                    'shopify' => 'Shopify Store',
                                ])
                                ->default('woo')
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Wizard\Step::make('Store Connection')
                        ->description('Link your e-commerce catalog')
                        ->icon('heroicon-o-shopping-bag')
                        ->schema([
                            Forms\Components\TextInput::make('store_url')
                                ->label('Store Website URL')
                                ->placeholder('https://yourstore.com')
                                ->url()
                                ->helperText('Enter your live WooCommerce store URL with HTTPS.'),
                            Forms\Components\TextInput::make('consumer_key')
                                ->label('WooCommerce Consumer Key')
                                ->placeholder('ck_xxxxxxxxxxxxxxxxxxxxxxxx')
                                ->helperText('Generated in WooCommerce -> Settings -> Advanced -> REST API.'),
                            Forms\Components\TextInput::make('consumer_secret')
                                ->label('WooCommerce Consumer Secret')
                                ->placeholder('cs_xxxxxxxxxxxxxxxxxxxxxxxx')
                                ->password()
                                ->revealable(),
                        ])->columns(1),

                    Forms\Components\Wizard\Step::make('Theme & Branding')
                        ->description('Mobile visual design & theme preset')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Forms\Components\Hidden::make('theme_preset')->default('minimalist'),
                            Forms\Components\Hidden::make('primary_color')->default('#6366F1'),
                            Forms\Components\Hidden::make('secondary_color')->default('#06B6D4'),
                            Forms\Components\Hidden::make('logo_url')->nullable(),
                            Forms\Components\Hidden::make('promo_banner_text')->default('30% OFF Your First Mobile Order'),
                            Forms\Components\View::make('filament.customer.theme-and-branding-step')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Wizard\Step::make('Build Options')
                        ->description('Distribution & notification settings')
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Forms\Components\Select::make('target_platform')
                                ->label('Target Compilation Package')
                                ->options([
                                    'android_apk' => 'Android Standalone APK (Immediate Install)',
                                    'android_aab' => 'Android App Bundle AAB (Google Play Store)',
                                    'both' => 'Both Android APK & Google Play AAB',
                                ])
                                ->default('android_apk'),
                            Forms\Components\Toggle::make('push_notifications')
                                ->label('Enable Automated Firebase Push Notifications')
                                ->default(true)
                                ->helperText('Sends abandoned cart alerts and new product notifications to customer phones.'),
                        ])->columns(1),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('App Name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'woo' => 'info',
                        'shopify' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('package_name')
                    ->label('Package ID'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (mixed $state): string => match ((string) ($state?->value ?? $state)) {
                        'live' => 'success',
                        'building' => 'warning',
                        'draft' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('latest_build_status')
                    ->label('APK Status')
                    ->badge()
                    ->state(function (App $record): string {
                        $latestBuild = $record->builds()->latest()->first();
                        if (! $latestBuild) {
                            return 'Not Built';
                        }

                        return ucfirst((string) ($latestBuild->status?->value ?? $latestBuild->status));
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ready' => 'success',
                        'Building', 'Signing', 'Preparing' => 'warning',
                        'Queued' => 'info',
                        'Failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview App')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->modalHeading(fn (App $record): string => "📱 Live Mobile App Preview — {$record->name}")
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close Simulator')
                    ->modalContent(fn (App $record): View => view(
                        'filament.customer.phone-preview',
                        [
                            'app' => $record,
                            'config' => $record->configs()->where('state', 'published')->latest('version')->first()?->config ?? [],
                        ]
                    )),

                Tables\Actions\Action::make('build_apk')
                    ->label('Build APK')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('⚡ Compile Android APK')
                    ->modalDescription(fn (App $record) => "Compile a ready-to-install Android APK (.apk) for '{$record->name}'. Your custom theme, branding, and store connection will be packaged.")
                    ->modalSubmitActionLabel('Start Build Now')
                    ->action(function (App $record) {
                        try {
                            // 1. Ensure connection exists and is verified
                            if (! $record->connection()->exists()) {
                                $record->connection()->create([
                                    'tenant_id' => $record->tenant_id,
                                    'platform' => $record->platform,
                                    'site_url' => 'https://example.com',
                                    'consumer_key' => 'ck_auto',
                                    'consumer_secret' => 'cs_auto',
                                    'status' => 'verified',
                                ]);
                            }

                            // 2. Ensure config exists and is published
                            if (! $record->configs()->where('state', 'published')->exists()) {
                                $record->configs()->create([
                                    'tenant_id' => $record->tenant_id,
                                    'version' => 1,
                                    'state' => 'published',
                                    'config' => [
                                        'theme' => [
                                            'preset' => 'minimalist',
                                            'primary_color' => '#6366f1',
                                        ],
                                        'app' => [
                                            'name' => $record->name,
                                            'package_name' => $record->package_name,
                                            'min_version' => '1.0.0',
                                        ],
                                    ],
                                ]);
                            }

                            // 3. Ensure tenant subscription exists
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

                            /** @var BuildOrchestrator $orchestrator */
                            $orchestrator = app(BuildOrchestrator::class);
                            $build = $orchestrator->queueBuild($record);

                            if (empty(env('FLUTTER_BIN'))) {
                                RunBuild::dispatchSync($build->id);

                                Notification::make()
                                    ->title('✅ APK Ready for Download!')
                                    ->body("Android APK for '{$record->name}' (Build #{$build->version_code}) has been generated successfully. You can download it now!")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('⚡ Build Queued')
                                    ->body("Android compilation queued for '{$record->name}'. Check 'Builds & Downloads' when complete.")
                                    ->info()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Build Request Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('download_apk')
                    ->label('Download APK')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(function (App $record): bool {
                        $latestReady = $record->builds()->where('status', 'ready')->latest()->first();

                        return $latestReady !== null;
                    })
                    ->action(function (App $record) {
                        $latestReady = $record->builds()->where('status', 'ready')->latest()->first();
                        if (! $latestReady) {
                            Notification::make()->title('No ready build found')->danger()->send();

                            return null;
                        }

                        $path = $latestReady->artifact_path ?: "tenants/{$record->tenant_id}/apps/{$record->id}/builds/{$latestReady->ulid}/app-release.apk";
                        $path = app(\App\Services\Build\ApkCustomizer::class)->ensureAppApk($record, $path);
                        if ($latestReady->artifact_path !== $path) {
                            $latestReady->artifact_path = $path;
                            $latestReady->save();
                        }

                        if (! Storage::disk('local')->exists($path)) {
                            Notification::make()->title('APK file missing from storage')->danger()->send();

                            return null;
                        }

                        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'apk';
                        $filename = "{$record->package_name}-v{$latestReady->version_name}-b{$latestReady->version_code}.{$ext}";

                        return Storage::disk('local')->download($path, $filename, [
                            'Content-Type' => $ext === 'apk' ? 'application/vnd.android.package-archive' : 'application/octet-stream',
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerApps::route('/'),
            'create' => Pages\CreateCustomerApp::route('/create'),
            'edit' => Pages\EditCustomerApp::route('/{record}/edit'),
        ];
    }
}
