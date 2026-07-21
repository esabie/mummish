<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = trim((string) env('ADMIN_PHONE', ''));
        if ($phone === '') {
            $phone = trim((string) env('ADMIN_NOTIFICATION_PHONE', '0201854694'));
        }

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mummish.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'phone' => $phone !== '' ? $phone : null,
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
