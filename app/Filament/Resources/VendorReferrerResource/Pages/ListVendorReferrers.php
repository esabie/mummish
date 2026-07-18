<?php

namespace App\Filament\Resources\VendorReferrerResource\Pages;

use App\Filament\Resources\VendorReferrerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVendorReferrers extends ListRecords
{
    protected static string $resource = VendorReferrerResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->withSum('rewards', 'amount_cents');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
