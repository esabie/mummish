<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendPasswordResetSms;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_is_sent_by_sms_for_vendors_with_phone(): void
    {
        Bus::fake();

        $user = $this->createVendorWithPhone('0241234567');

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', 'We have sent your password reset link via SMS.');

        Bus::assertDispatched(SendPasswordResetSms::class, function (SendPasswordResetSms $job) use ($user) {
            return $job->phone === '0241234567'
                && $job->firstName === 'Ama'
                && str_contains($job->resetUrl, '/r/')
                && ! str_contains($job->resetUrl, '/reset-password/');
        });
    }

    public function test_reset_password_link_request_fails_without_vendor_phone(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        Bus::assertNotDispatched(SendPasswordResetSms::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = $this->createVendorWithPhone('0241234567');
        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/'.$token.'?email='.urlencode($user->email));

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->createVendorWithPhone('0241234567');
        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    private function createVendorWithPhone(string $phone): User
    {
        $user = User::factory()->create([
            'name' => 'Ama Mensah',
            'role' => UserRole::Vendor,
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => $phone,
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }
}
