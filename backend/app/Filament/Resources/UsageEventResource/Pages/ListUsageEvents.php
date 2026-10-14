<?php

namespace App\Filament\Resources\UsageEventResource\Pages;

use App\Filament\Resources\UsageEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsageEvents extends ListRecords
{
    protected static string $resource = UsageEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
