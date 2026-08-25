<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendCustomerWelcomeSms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('orders')
                ->where('shop', null));
    }

    public function test_profile_shows_order_history_for_authenticated_buyer(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'LH-ACCOUNT-1',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'ref-account-1',
            'customer_name' => 'Buyer',
            'customer_email' => $user->email,
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market St',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 5000,
            'shipping_cents' => 1500,
            'discount_cents' => 0,
            'total_cents' => 6500,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'vendor_user_id' => User::factory()->create()->id,
            'product_title' => 'Wooden blocks',
            'product_brand' => 'Mummish',
            'product_sku' => 'SKU-1',
            'product_image' => 'https://example.com/blocks.jpg',
            'attributes' => null,
            'unit_price_cents' => 5000,
            'quantity' => 1,
            'line_total_cents' => 5000,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('orders', 1)
                ->where('orders.0.order_number', 'LH-ACCOUNT-1')
                ->where('orders.0.formatted_total', 'GHS 65.00')
                ->where('orders.0.items.0.title', 'Wooden blocks')
                ->where('orders.0.is_paid', true));
    }

    public function test_profile_shows_vendor_shop_summary(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Vendor,
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'shop_slug' => 'little-knot',
            'business_email' => $user->email,
            'phone' => '0241111111',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->where('shop.has_application', true)
                ->where('shop.shop_name', 'Little Knot')
                ->where('shop.status', 'approved')
                ->where('shop.shop_slug', 'little-knot')
                ->where('shop.storefront_url', route('shops.show', 'little-knot')));
    }

    public function test_profile_information_cannot_be_updated(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response->assertMethodNotAllowed();

        $user->refresh();

        $this->assertSame('Original Name', $user->name);
        $this->assertSame('original@example.com', $user->email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('password');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticated();
    }

    public function test_vendor_account_deletion_removes_listings_but_keeps_admin_record(): void
    {
        $vendor = User::factory()->vendor()->create([
            'name' => 'Seller Kofi',
            'email' => 'kofi-shop@example.com',
        ]);

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
            'shop_slug' => 'kofi-kids',
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden blocks',
            'sku' => 'KOFI-1',
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 4,
            'status' => ProductStatus::Active,
        ]);
        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Draft rattle',
            'sku' => 'KOFI-2',
            'category' => 'toys_development',
            'price_cents' => 1200,
            'stock_quantity' => 2,
            'status' => ProductStatus::Draft,
        ]);

        $this->actingAs($vendor)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($vendor);
        $this->assertSame(0, Product::query()->where('user_id', $vendor->id)->count());

        $application = VendorApplication::query()->where('user_id', $vendor->id)->first();
        $this->assertNotNull($application);
        $this->assertSame(VendorApplicationStatus::Deleted, $application->status);

        $deleted = User::withTrashed()->find($vendor->id);
        $this->assertNotNull($deleted);
        $this->assertSame('kofi-shop@example.com', $deleted->email_before_deletion);
        $this->assertSame('kofi-shop@example.com', $deleted->displayEmail());
        $this->assertNotSame('kofi-shop@example.com', $deleted->email);
        $this->assertNull(User::query()->where('email', 'kofi-shop@example.com')->first());
    }

    public function test_deleted_account_email_can_be_reused_to_register(): void
    {
        Bus::fake([SendCustomerWelcomeSms::class]);

        $user = User::factory()->create([
            'email' => 'come-back@example.com',
            'phone' => '0241112233',
        ]);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertGuest();

        $this->post('/register', [
            'name' => 'Returning Shopper',
            'email' => 'come-back@example.com',
            'phone' => '0249988776',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'come-back@example.com',
            'name' => 'Returning Shopper',
        ]);
        $this->assertSame(2, User::withTrashed()->where(function ($query) {
            $query->where('email', 'come-back@example.com')
                ->orWhere('email_before_deletion', 'come-back@example.com');
        })->count());
    }

    public function test_legacy_soft_deleted_email_is_freed_during_signup_check(): void
    {
        Bus::fake([SendCustomerWelcomeSms::class]);

        $legacy = User::factory()->create([
            'email' => 'legacy-hold@example.com',
        ]);
        $legacy->delete();

        $this->assertSame('legacy-hold@example.com', $legacy->fresh()->email);

        $this->post('/register', [
            'name' => 'Fresh Account',
            'email' => 'legacy-hold@example.com',
            'phone' => '0241122334',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'legacy-hold@example.com',
            'name' => 'Fresh Account',
        ]);

        $legacy->refresh();
        $this->assertSame('legacy-hold@example.com', $legacy->email_before_deletion);
        $this->assertNotSame('legacy-hold@example.com', $legacy->email);
    }

    public function test_admin_can_see_vendor_who_deleted_their_account(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0201111111']);
        $vendor = User::factory()->vendor()->create([
            'name' => 'Seller Ama',
            'email' => 'ama-deleted@example.com',
        ]);

        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Boateng',
            'shop_name' => 'Ama Baby',
            'business_email' => $vendor->email,
            'phone' => '0245556677',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        $this->actingAs($vendor)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->actingAs($admin)
            ->get('/admin/vendors')
            ->assertOk()
            ->assertSee('Seller Ama')
            ->assertSee('Ama Baby')
            ->assertSee('ama-deleted@example.com')
            ->assertSee('Deleted');

        $this->actingAs($admin)
            ->get('/admin/vendor-applications')
            ->assertOk()
            ->assertSee('Ama Baby')
            ->assertSee('Account deleted');
    }

    public function test_deleted_vendor_cannot_log_in(): void
    {
        $vendor = User::factory()->vendor()->create([
            'email' => 'gone@example.com',
        ]);

        $this->actingAs($vendor)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->post('/login', [
            'email' => 'gone@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_cannot_delete_their_account_from_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertForbidden();

        $this->assertNotNull($admin->fresh());
        $this->assertAuthenticated();
    }
}
