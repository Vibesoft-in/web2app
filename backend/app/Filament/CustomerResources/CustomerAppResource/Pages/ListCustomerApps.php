<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerApps extends ListRecords
{
    protected static string $resource = CustomerAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create New Mobile App'),
        ];
    }
}
