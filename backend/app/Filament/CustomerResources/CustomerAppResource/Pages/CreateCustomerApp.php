<?php

namespace App\Filament\CustomerResources\CustomerAppResource\Pages;

use App\Filament\CustomerResources\CustomerAppResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerApp extends CreateRecord
{
    protected static string $resource = CustomerAppResource::class;
}
