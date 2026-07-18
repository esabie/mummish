<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_users_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertMethodNotAllowed();

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh());
    }
}
