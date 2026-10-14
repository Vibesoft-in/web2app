<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\CustomerAppResource\Pages;
use App\Models\App;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Section::make('App Identity')
                    ->description('Set up your white-label mobile app.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('App Name')
                            ->placeholder('e.g. My Fashion Store')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('package_name')
                            ->label('Android Package Name / Bundle ID')
                            ->placeholder('e.g. com.mystore.app')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('platform')
                            ->label('E-Commerce Platform')
                            ->options([
                                'woo' => 'WooCommerce',
                                'shopify' => 'Shopify',
                            ])
                            ->default('woo')
                            ->required(),
                    ])->columns(2),
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
