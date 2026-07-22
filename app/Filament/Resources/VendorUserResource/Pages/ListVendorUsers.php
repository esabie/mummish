<?php

namespace App\Filament\Resources\VendorUserResource\Pages;

use App\Filament\Concerns\HasUsersHubBreadcrumbs;
use App\Filament\Resources\VendorUserResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorUsers extends ListRecords
{
    use HasUsersHubBreadcrumbs;

    protected static string $resource = VendorUserResource::class;
}
