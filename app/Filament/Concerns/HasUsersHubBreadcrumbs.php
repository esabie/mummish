<?php

namespace App\Filament\Concerns;

use App\Filament\Pages\UsersHub;

trait HasUsersHubBreadcrumbs
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return array_merge(
            [UsersHub::getUrl() => 'Users'],
            parent::getBreadcrumbs(),
        );
    }
}
