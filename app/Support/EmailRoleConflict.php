<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class EmailRoleConflict
{
    /**
     * Message when an email is already taken for a different signup path.
     * Returns null when the email is available.
     */
    public static function customerRegistrationMessage(string $email): ?string
    {
        $existing = self::find($email);

        if ($existing === null) {
            return null;
        }

        return match ($existing->role) {
            UserRole::Vendor => 'This email already belongs to a vendor account. Sign in with that account, or use a different email to register as a customer.',
            UserRole::Admin => 'This email belongs to an admin account and cannot be used for a customer signup. Use a different email.',
            default => 'An account with this email already exists. Please sign in instead.',
        };
    }

    /**
     * Message when an email is already taken on guest vendor signup.
     * Returns null when the email is available.
     */
    public static function vendorRegistrationMessage(string $email): ?string
    {
        $existing = self::find($email);

        if ($existing === null) {
            return null;
        }

        return match ($existing->role) {
            UserRole::Admin => 'This email belongs to an admin account, which cannot become a vendor. Use a different email to sell.',
            UserRole::Vendor => 'A vendor account with this email already exists. Please sign in instead.',
            default => 'This email already belongs to a customer account. Sign in with that account to apply as a vendor, or use a different email.',
        };
    }

    private static function find(string $email): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }
}
