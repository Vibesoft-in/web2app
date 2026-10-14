<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BuildResource\Pages;
use App\Models\Build;
use App\Services\Build\BuildOrchestrator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BuildResource extends Resource
{
    protected static ?string $model = Build::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Builds & Infrastructure';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version_code')->disabled(),
                Forms\Components\TextInput::make('version_name')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\TextInput::make('artifact_path')->disabled(),
                Forms\Components\Textarea::make('error')->disabled()->rows(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('app.name')->label('App')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (mixed $state): string => match ((string) ($state?->value ?? $state)) {
                        'ready' => 'success',
                        'failed' => 'danger',
                        'building', 'signing', 'preparing' => 'warning',
                        'queued' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('version_code')->label('V-Code')->sortable(),
                Tables\Columns\TextColumn::make('version_name')->label('Version'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'preparing' => 'Preparing',
                        'building' => 'Building',
                        'signing' => 'Signing',
                        'ready' => 'Ready',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_logs')
                    ->label('View Logs')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading(fn (Build $record) => "Sanitized Build Logs — Build #{$record->id} ({$record->app?->name})")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([
                        Forms\Components\Textarea::make('logs')
                            ->rows(18)
                            ->default(function (Build $record): string {
                                if (! $record->logs_path || ! Storage::disk('local')->exists($record->logs_path)) {
                                    return 'No logs recorded yet for this build.';
                                }

                                return Storage::disk('local')->get($record->logs_path);
                            })
                            ->disabled(),
                    ]),
                Tables\Actions\Action::make('retry')
                    ->label('Retry Build')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Retry Android Build')
                    ->modalDescription(fn (Build $record) => "Trigger a fresh compilation for {$record->app?->name}? A new build job will be dispatched.")
                    ->action(function (Build $record) {
                        try {
                            $newBuild = app(BuildOrchestrator::class)->queueBuild($record->app);

                            Notification::make()
                                ->title('Build Queued')
                                ->body("Build #{$newBuild->id} has been queued successfully.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Retry Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBuilds::route('/'),
        ];
    }
}
