<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\CustomerBuildResource\Pages;
use App\Models\Build;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerBuildResource extends Resource
{
    protected static ?string $model = Build::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $modelLabel = 'App Build';

    protected static ?string $pluralModelLabel = 'Builds & Downloads';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version_name')->label('Version Name')->disabled(),
                Forms\Components\TextInput::make('version_code')->label('Version Code')->disabled(),
                Forms\Components\TextInput::make('status')->label('Status')->disabled(),
                Forms\Components\TextInput::make('artifact_path')->label('Binary Artifact')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app.name')->label('App Name'),
                Tables\Columns\TextColumn::make('version_name')->label('Version'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (mixed $state): string => match ((string) ($state?->value ?? $state)) {
                        'ready' => 'success',
                        'failed' => 'danger',
                        'building', 'signing', 'preparing' => 'warning',
                        'queued' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerBuilds::route('/'),
        ];
    }
}
