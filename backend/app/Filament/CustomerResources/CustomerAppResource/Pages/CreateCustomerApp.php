<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateCustomerApp extends CreateRecord
{
    protected static string $resource = CustomerAppResource::class;

    protected function getRedirectUrl(): string
    {
        return CustomerAppResource::getUrl('index', panel: 'app');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'package_name' => 'This Package ID ('.($data['package_name'] ?? '').') is already registered. Please choose a unique package name (e.g. com.yourbrand.app).',
            ]);
        }
    }
}
