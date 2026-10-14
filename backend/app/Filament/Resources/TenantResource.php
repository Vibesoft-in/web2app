<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Tenancy & Clients';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'suspended' => 'Suspended',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('owner.email')
                    ->label('Owner Email')
                    ->searchable()
                    ->default(fn (Tenant $record) => $record->users()->where('role', 'owner')->first()?->email ?? '—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('apps_count')
                    ->counts('apps')
                    ->label('Apps')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate Tenant Owner')
                    ->modalDescription(fn (Tenant $record) => "You are about to switch sessions into tenant '{$record->name}'. All actions will be logged to the audit trail.")
                    ->action(function (Tenant $record) {
                        /** @var User|null $ownerUser */
                        $ownerUser = $record->users()->where('role', 'owner')->first() ?? $record->users()->first();

                        if (! $ownerUser) {
                            Notification::make()
                                ->title('Cannot Impersonate')
                                ->body('No user accounts exist for this tenant.')
                                ->danger()
                                ->send();

                            return;
                        }

                        /** @var User $currentUser */
                        $currentUser = auth()->user();
                        app(ImpersonationService::class)->start($currentUser, $ownerUser);

                        Notification::make()
                            ->title('Impersonation Active')
                            ->body("Now managing as {$ownerUser->email}")
                            ->warning()
                            ->send();
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
