<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Jobs\SendPasswordResetSms;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_request_page_can_be_rendered(): void
    {
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_admin_password_reset_link_is_sent_by_sms(): void
    {
        Bus::fake();

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@mummish.com',
            'phone' => '0201854694',
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $admin->email])
            ->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified();

        Bus::assertDispatched(SendPasswordResetSms::class, function (SendPasswordResetSms $job) use ($admin) {
            return $job->phone === '0201854694'
                && $job->firstName === 'Admin'
                && str_contains($job->resetUrl, '/admin/password-reset/reset')
                && str_contains($job->resetUrl, 'email='.urlencode($admin->email));
        });
    }

    public function test_admin_password_reset_requires_phone_on_the_admin_user(): void
    {
        Bus::fake();
        config(['marketplace.admin_notification_phones' => ['0249998888']]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@mummish.com',
            'phone' => null,
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $admin->email])
            ->call('request')
            ->assertNotified();

        Bus::assertNotDispatched(SendPasswordResetSms::class);
    }

    public function test_non_admin_cannot_request_filament_password_reset_sms(): void
    {
        Bus::fake();

        $vendor = User::factory()->create([
            'email' => 'vendor@example.com',
            'role' => UserRole::Vendor,
            'phone' => '0241234567',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $vendor->email])
            ->call('request')
            ->assertNotified();

        Bus::assertNotDispatched(SendPasswordResetSms::class);
    }

    public function test_admin_can_reset_password_with_valid_token(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@mummish.com',
            'phone' => '0201854694',
            'role' => UserRole::Admin,
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($admin);
        $url = Filament::getResetPasswordUrl($token, $admin);

        $this->get($url)->assertOk();

        Livewire::test(\Filament\Pages\Auth\PasswordReset\ResetPassword::class, [
            'email' => $admin->email,
            'token' => $token,
        ])
            ->fillForm([
                'email' => $admin->email,
                'password' => 'new-admin-password',
                'passwordConfirmation' => 'new-admin-password',
            ])
            ->call('resetPassword');

        $this->assertTrue(Hash::check('new-admin-password', $admin->fresh()->password));
    }
}
