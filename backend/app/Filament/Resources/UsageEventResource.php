<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsageEventResource\Pages;
use App\Models\UsageEvent;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UsageEventResource extends Resource
{
    protected static ?string $model = UsageEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Usage & Cost';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('app.name')
                    ->label('App')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'build' => 'primary',
                        'push', 'push_notification' => 'info',
                        'whatsapp', 'whatsapp_message' => 'success',
                        'subscription_payment' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Cost')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'build' => 'Build',
                        'push' => 'Push Notification',
                        'whatsapp' => 'WhatsApp Message',
                        'subscription_payment' => 'Subscription Payment',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsageEvents::route('/'),
        ];
    }
}
