<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\AdminUserResource;
use App\Filament\Resources\CustomerUserResource;
use App\Filament\Resources\NewsletterCustomerResource;
use App\Filament\Resources\VendorUserResource;
use App\Models\NewsletterCustomer;
use App\Models\User;
use Filament\Pages\Page;

class UsersHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $title = 'Users';

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.users-hub';

    /**
     * @return array{cards: list<array{title: string, description: string, count: int, url: string, icon: string}>}
     */
    protected function getViewData(): array
    {
        return [
            'cards' => [
                [
                    'title' => 'Customers',
                    'description' => 'Shoppers with customer accounts',
                    'count' => User::query()->where('role', UserRole::Customer)->count(),
                    'url' => CustomerUserResource::getUrl('index'),
                    'icon' => 'heroicon-o-shopping-bag',
                ],
                [
                    'title' => 'Vendors',
                    'description' => 'Seller accounts on the marketplace',
                    'count' => User::query()->where('role', UserRole::Vendor)->count(),
                    'url' => VendorUserResource::getUrl('index'),
                    'icon' => 'heroicon-o-building-storefront',
                ],
                [
                    'title' => 'Newsletter list',
                    'description' => 'People who joined from the site footer',
                    'count' => NewsletterCustomer::query()->count(),
                    'url' => NewsletterCustomerResource::getUrl('index'),
                    'icon' => 'heroicon-o-megaphone',
                ],
                [
                    'title' => 'Admin users',
                    'description' => 'Staff who can access this portal',
                    'count' => User::query()->where('role', UserRole::Admin)->count(),
                    'url' => AdminUserResource::getUrl('index'),
                    'icon' => 'heroicon-o-shield-check',
                ],
            ],
        ];
    }
}
