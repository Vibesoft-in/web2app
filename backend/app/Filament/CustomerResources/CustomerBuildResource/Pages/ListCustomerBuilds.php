<?php

namespace App\Filament\CustomerResources\CustomerBuildResource\Pages;

use App\Filament\CustomerResources\CustomerBuildResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerBuilds extends ListRecords
{
    protected static string $resource = CustomerBuildResource::class;
}
