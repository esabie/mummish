<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0241234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $user = User::first();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('0241234567', $user->phone);
    }

    public function test_registration_requires_phone_number(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cannot_register_customer_with_existing_vendor_email(): void
    {
        User::factory()->vendor()->create([
            'email' => 'seller@example.com',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Buyer',
            'email' => 'seller@example.com',
            'phone' => '0241234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'This email already belongs to a vendor account. Sign in with that account, or use a different email to register as a customer.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(UserRole::Vendor, User::first()->role);
    }

    public function test_cannot_register_customer_with_existing_customer_email(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
            'role' => UserRole::Customer,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Buyer Two',
            'email' => 'buyer@example.com',
            'phone' => '0241234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }
}
