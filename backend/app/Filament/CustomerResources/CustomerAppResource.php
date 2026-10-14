<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\CustomerAppResource\Pages;
use App\Models\App;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CustomerAppResource extends Resource
{
    protected static ?string $model = App::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $modelLabel = 'Mobile App';

    protected static ?string $pluralModelLabel = 'My Mobile Apps';

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
                                ->dehydrated(false)
                                ->helperText('Enter your live WooCommerce store URL with HTTPS.'),
                            Forms\Components\TextInput::make('consumer_key')
                                ->label('WooCommerce Consumer Key')
                                ->placeholder('ck_xxxxxxxxxxxxxxxxxxxxxxxx')
                                ->dehydrated(false)
                                ->helperText('Generated in WooCommerce -> Settings -> Advanced -> REST API.'),
                            Forms\Components\TextInput::make('consumer_secret')
                                ->label('WooCommerce Consumer Secret')
                                ->placeholder('cs_xxxxxxxxxxxxxxxxxxxxxxxx')
                                ->password()
                                ->dehydrated(false)
                                ->revealable(),
                        ])->columns(1),

                    Forms\Components\Wizard\Step::make('Theme & Branding')
                        ->description('Mobile visual design & theme preset')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Forms\Components\Radio::make('theme_preset')
                                ->label('Select Theme Preset')
                                ->options([
                                    'minimalist' => 'Theme A: Modern Minimalist (Clean card layouts, subtle shadows, Inter font)',
                                    'boutique' => 'Theme B: Boutique Glamour (Vibrant accents, rounded cards, Poppins font)',
                                    'apex' => 'Theme C: Apex Dark Luxury (Sleek dark mode luxury palette with Outfit typography)',
                                ])
                                ->default('minimalist')
                                ->dehydrated(false),
                            Forms\Components\ColorPicker::make('primary_color')
                                ->label('Primary Brand Color')
                                ->default('#6366f1')
                                ->dehydrated(false),
                        ])->columns(1),

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
                                ->default('android_apk')
                                ->dehydrated(false),
                            Forms\Components\Toggle::make('push_notifications')
                                ->label('Enable Automated Firebase Push Notifications')
                                ->default(true)
                                ->dehydrated(false)
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y'),
            ])
            ->actions([
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
