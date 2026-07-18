<?php

namespace App\Filament\Resources\VendorApplicationResource\Pages;

use App\Filament\Resources\VendorApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorApplications extends ListRecords
{
    protected static string $resource = VendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
