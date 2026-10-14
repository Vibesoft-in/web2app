<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class EditCustomerApp extends EditRecord
{
    protected static string $resource = CustomerAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return CustomerAppResource::getUrl('index', panel: 'app');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'package_name' => 'This Package ID ('.($data['package_name'] ?? '').') is already registered. Please choose a unique package name (e.g. com.yourbrand.app).',
            ]);
        }
    }
}
