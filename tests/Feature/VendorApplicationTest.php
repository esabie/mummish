<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendAccountWelcomeSms;
use App\Jobs\SendAdminNewVendorRegistrationSms;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorReferrer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'email' => 'ama@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'shop_name' => 'Little Knot',
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-123456789-0',
            'category' => 'toys_development',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_vendor_application_requires_terms_acceptance(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'terms_accepted' => false,
        ]));

        $response->assertSessionHasErrors('terms_accepted');
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_vendor_application_requires_phone(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'phone' => '',
        ]));

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_vendor_application_requires_ghana_card_id(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'ghana_card_id' => '',
        ]));

        $response->assertSessionHasErrors('ghana_card_id');
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_vendor_application_rejects_invalid_ghana_card_id(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'ghana_card_id' => 'ABC-123',
        ]));

        $response->assertSessionHasErrors('ghana_card_id');
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_vendor_application_normalizes_ghana_card_id_without_dashes(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'ghana_card_id' => 'GHA1234567890',
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $this->assertSame('GHA-123456789-0', VendorApplication::first()->ghana_card_id);
    }

    public function test_vendor_application_rejects_duplicate_ghana_card_id(): void
    {
        $user = User::factory()->create();

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Existing',
            'last_name' => 'Vendor',
            'shop_name' => 'Existing Shop',
            'business_email' => $user->email,
            'phone' => '0241111111',
            'ghana_card_id' => 'GHA-123456789-0',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'ghana_card_id' => 'GHA-123456789-0',
        ]));

        $response->assertSessionHasErrors('ghana_card_id');
        $this->assertDatabaseCount('vendor_applications', 1);
    }

    public function test_vendor_application_requires_account_credentials_for_guests(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_vendor_application_dispatches_welcome_sms_job(): void
    {
        Bus::fake();

        $this->post(route('vendor.signup.store'), $this->validPayload());

        Bus::assertDispatched(SendAccountWelcomeSms::class, function (SendAccountWelcomeSms $job) {
            return $job->phone === '0241234567'
                && $job->firstName === 'Ama';
        });

        Bus::assertDispatched(SendAdminNewVendorRegistrationSms::class, function (SendAdminNewVendorRegistrationSms $job) {
            return $job->application->shop_name === 'Little Knot'
                && $job->application->first_name === 'Ama';
        });
    }

    public function test_authenticated_vendor_application_does_not_dispatch_welcome_sms(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'email' => 'seller@example.com',
        ]);

        $this->actingAs($user)->post(route('vendor.signup.store'), [
            'first_name' => 'Kofi',
            'last_name' => 'Boateng',
            'shop_name' => 'Tiny Threads',
            'phone' => '0551234567',
            'ghana_card_id' => 'GHA-987654321-1',
            'category' => 'clothing_footwear',
            'terms_accepted' => true,
        ]);

        Bus::assertNotDispatched(SendAccountWelcomeSms::class);
        Bus::assertNotDispatched(SendAdminNewVendorRegistrationSms::class);
    }

    public function test_guest_vendor_application_creates_user_logs_in_and_saves_application(): void
    {
        $response = $this->post(route('vendor.signup.store'), $this->validPayload());

        $response->assertRedirect(route('vendor.inventory.index'));
        $response->assertSessionHas('vendorApplicationSubmitted', true);
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('vendor_applications', 1);

        $user = User::first();
        $this->assertSame('ama@example.com', $user->email);
        $this->assertSame('Ama Mensah', $user->name);
        $this->assertSame(UserRole::Vendor, $user->role);

        $application = VendorApplication::first();
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame('ama@example.com', $application->business_email);
        $this->assertSame('GHA-123456789-0', $application->ghana_card_id);
        $this->assertTrue($application->terms_accepted);
    }

    public function test_vendor_application_stores_optional_referral_code(): void
    {
        VendorReferrer::create([
            'code' => 'PARTNER2026',
            'name' => 'Partner',
            'is_active' => true,
        ]);

        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'referral_code' => 'partner2026',
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));
        $application = VendorApplication::first();
        $this->assertSame('PARTNER2026', $application->referral_code);
        $this->assertNotNull($application->vendor_referrer_id);
    }

    public function test_vendor_application_rejects_invalid_referral_code(): void
    {
        VendorReferrer::create([
            'code' => 'VALIDCODE',
            'name' => 'Partner',
            'is_active' => true,
        ]);

        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'referral_code' => 'WRONGCODE',
        ]));

        $response->assertSessionHasErrors('referral_code');
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_vendor_signup_prefills_referral_code_from_query_string(): void
    {
        VendorReferrer::create([
            'code' => 'PARTNER2026',
            'name' => 'Partner',
            'is_active' => true,
        ]);

        $this->get(route('vendor.signup', ['ref' => 'partner2026']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vendor/SignUp')
                ->where('referral_code', 'PARTNER2026')
                ->where('isAdminAccount', false)
            );
    }

    public function test_authenticated_user_can_submit_vendor_application_without_password(): void
    {
        $user = User::factory()->create([
            'email' => 'seller@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('vendor.signup.store'), [
            'first_name' => 'Kofi',
            'last_name' => 'Boateng',
            'shop_name' => 'Tiny Threads',
            'phone' => '0551234567',
            'ghana_card_id' => 'GHA-987654321-1',
            'category' => 'clothing_footwear',
            'terms_accepted' => true,
        ]);

        $response->assertRedirect(route('vendor.inventory.index'));
        $this->assertDatabaseCount('vendor_applications', 1);
        $this->assertSame($user->id, VendorApplication::first()->user_id);
        $user->refresh();
        $this->assertSame(UserRole::Vendor, $user->role);
    }

    public function test_guest_vendor_application_can_upload_shop_logo(): void
    {
        Storage::fake('public');

        $response = $this->post(route('vendor.signup.store'), array_merge($this->validPayload(), [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $application = VendorApplication::first();
        $this->assertNotNull($application->logo_path);
        Storage::disk('public')->assertExists($application->logo_path);
    }

    public function test_vendor_application_rejects_invalid_logo_file(): void
    {
        Storage::fake('public');

        $response = $this->post(route('vendor.signup.store'), array_merge($this->validPayload(), [
            'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]));

        $response->assertSessionHasErrors('logo');
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_user_cannot_submit_duplicate_vendor_application(): void
    {
        $user = User::factory()->create();

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Existing Shop',
            'business_email' => $user->email,
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-111111111-1',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post(route('vendor.signup.store'), [
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Another Shop',
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-222222222-2',
            'category' => 'toys_development',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('vendor_applications', 1);
    }

    public function test_authenticated_admin_cannot_submit_vendor_application(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($admin)->post(route('vendor.signup.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'shop_name' => 'Should Not Exist',
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-333333333-3',
            'category' => 'toys_development',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('vendor_applications', 0);
        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
    }

    public function test_guest_cannot_apply_with_existing_admin_email(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'email' => 'admin@example.com',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'This email belongs to an admin account, which cannot become a vendor. Use a different email to sell.',
        ]);
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(UserRole::Admin, User::first()->role);
    }

    public function test_guest_cannot_apply_with_existing_customer_email_without_logging_in(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
            'role' => UserRole::Customer,
        ]);

        $response = $this->post(route('vendor.signup.store'), $this->validPayload([
            'email' => 'buyer@example.com',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'This email already belongs to a customer account. Sign in with that account to apply as a vendor, or use a different email.',
        ]);
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(UserRole::Customer, User::first()->role);
    }

    public function test_vendor_signup_page_flags_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('vendor.signup'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vendor/SignUp')
                ->where('isAdminAccount', true)
            );
    }
}
