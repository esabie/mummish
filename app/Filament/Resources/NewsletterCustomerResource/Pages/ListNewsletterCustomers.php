<?php

namespace App\Filament\Resources\NewsletterCustomerResource\Pages;

use App\Filament\Concerns\HasUsersHubBreadcrumbs;
use App\Filament\Resources\NewsletterCustomerResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterCustomers extends ListRecords
{
    use HasUsersHubBreadcrumbs;

    protected static string $resource = NewsletterCustomerResource::class;
}
