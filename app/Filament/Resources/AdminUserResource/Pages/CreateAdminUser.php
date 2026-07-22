<?php

namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Concerns\HasUsersHubBreadcrumbs;
use App\Filament\Resources\AdminUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminUser extends CreateRecord
{
    use HasUsersHubBreadcrumbs;

    protected static string $resource = AdminUserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Admin;
        $data['email_verified_at'] = now();

        return $data;
    }
}
