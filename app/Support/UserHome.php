<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class UserHome
{
    public static function path(?User $user): string
    {
        if ($user === null) {
            return '/';
        }

        return match ($user->role) {
            UserRole::Vendor => route('vendor.inventory.index', absolute: false),
            UserRole::Admin => '/admin',
            default => route('dashboard', absolute: false),
        };
    }
}
