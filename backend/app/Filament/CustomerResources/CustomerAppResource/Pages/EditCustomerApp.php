<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerApp extends EditRecord
{
    protected static string $resource = CustomerAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
