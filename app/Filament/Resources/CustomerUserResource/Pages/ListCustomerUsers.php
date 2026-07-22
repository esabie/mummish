<?php

namespace App\Filament\Resources\CustomerUserResource\Pages;

use App\Filament\Concerns\HasUsersHubBreadcrumbs;
use App\Filament\Resources\CustomerUserResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerUsers extends ListRecords
{
    use HasUsersHubBreadcrumbs;

    protected static string $resource = CustomerUserResource::class;
}
