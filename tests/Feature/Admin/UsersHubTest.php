<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\NewsletterCustomer;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_hub_is_reachable_and_lists_show_correct_roles(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0201111111']);
        $customer = User::factory()->customer()->create(['name' => 'Buyer Ama']);
        $vendor = User::factory()->vendor()->create(['name' => 'Seller Kofi']);

        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'shop_name' => 'Kofi Kids',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        NewsletterCustomer::query()->create([
            'name' => 'Newsletter Joiner',
            'phone' => '0249998877',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee('Vendors')
            ->assertSee('Newsletter list')
            ->assertSee('Admin users');

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee('Buyer Ama')
            ->assertDontSee('Seller Kofi');

        $this->actingAs($admin)
            ->get('/admin/vendors')
            ->assertOk()
            ->assertSee('Seller Kofi')
            ->assertSee('Kofi Kids')
            ->assertDontSee('Buyer Ama');

        $this->actingAs($admin)
            ->get('/admin/newsletter-customers')
            ->assertOk()
            ->assertSee('0249998877');

        $this->actingAs($admin)
            ->get('/admin/admin-users')
            ->assertOk();

        $this->assertSame(UserRole::Customer, $customer->role);
        $this->assertSame(UserRole::Vendor, $vendor->role);
    }
}
