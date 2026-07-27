<?php

namespace Tests\Feature\Vendor;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_access_inventory_page(): void
    {
        $user = $this->vendorWithApplication();

        Product::create([
            'user_id' => $user->id,
            'title' => 'Test Toy',
            'sku' => 'SKU-1',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
            'image_url' => 'products/1/toy.jpg',
            'image_urls' => ['products/1/toy.jpg'],
        ]);

        $response = $this->actingAs($user)->get(route('vendor.inventory.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Vendor/Inventory/Index')
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Test Toy')
            ->where('products.data.0.image_url', fn ($url) => str_contains($url, '/storage/products/1/toy.jpg'))
        );
    }

    public function test_customer_cannot_access_vendor_inventory(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user)->get(route('vendor.inventory.index'))->assertForbidden();
    }

    public function test_vendor_without_application_redirects_to_signup(): void
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($user)->get(route('vendor.inventory.index'))
            ->assertRedirect(route('vendor.signup'));
    }

    public function test_vendor_dashboard_shows_commission_and_payout_breakdown(): void
    {
        $user = $this->vendorWithApplication();

        Product::create([
            'user_id' => $user->id,
            'title' => 'Fairly New Stroller',
            'sku' => 'STR-01',
            'category' => 'toys_development',
            'price_cents' => 50000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'A gently used stroller in excellent condition.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/stroller.jpg',
                'https://example.com/stroller-2.jpg',
                'https://example.com/stroller-3.jpg',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vendor/Dashboard')
                ->where('earnings.commission_percent', 10)
                ->where('earnings.totals.gross_cents', 0)
                ->where('payoutDetails.payment_method', null)
                ->has('ghanaBanks')
                ->where('ghanaBanks.0', fn ($bank) => is_string($bank) && $bank !== '')
                ->has('earnings.recent_sales', 0)
            );
    }

    public function test_vendor_can_save_bank_payment_details(): void
    {
        $user = $this->vendorWithApplication();

        $this->actingAs($user)
            ->put(route('vendor.payment-details.update'), [
                'payment_method' => 'bank',
                'bank_name' => 'GCB BANK',
                'bank_account_name' => 'Ama Mensah',
                'bank_account_number' => '0123456789',
            ])
            ->assertRedirect();

        $application = $user->fresh()->vendorApplication;

        $this->assertSame('bank', $application->payment_method);
        $this->assertSame('GCB BANK', $application->bank_name);
        $this->assertSame('Ama Mensah', $application->bank_account_name);
        $this->assertSame('0123456789', $application->bank_account_number);
        $this->assertNull($application->mobile_money_provider);
        $this->assertNull($application->mobile_money_name);
        $this->assertNull($application->mobile_money_number);
    }

    public function test_vendor_cannot_save_bank_name_outside_list(): void
    {
        $user = $this->vendorWithApplication();

        $this->actingAs($user)
            ->from(route('vendor.dashboard'))
            ->put(route('vendor.payment-details.update'), [
                'payment_method' => 'bank',
                'bank_name' => 'Not A Real Bank',
                'bank_account_name' => 'Ama Mensah',
                'bank_account_number' => '0123456789',
            ])
            ->assertRedirect(route('vendor.dashboard'))
            ->assertSessionHasErrors('bank_name');
    }

    public function test_vendor_can_save_mobile_money_details(): void
    {
        $user = $this->vendorWithApplication();

        $this->actingAs($user)
            ->put(route('vendor.payment-details.update'), [
                'payment_method' => 'mobile_money',
                'mobile_money_provider' => 'MTN MoMo',
                'mobile_money_name' => 'Ama Mensah',
                'mobile_money_number' => '0241234567',
            ])
            ->assertRedirect();

        $application = $user->fresh()->vendorApplication;

        $this->assertSame('mobile_money', $application->payment_method);
        $this->assertSame('MTN MoMo', $application->mobile_money_provider);
        $this->assertSame('Ama Mensah', $application->mobile_money_name);
        $this->assertSame('0241234567', $application->mobile_money_number);
        $this->assertNull($application->bank_name);
        $this->assertNull($application->bank_account_name);
        $this->assertNull($application->bank_account_number);
    }

    public function test_vendor_cannot_update_payment_details_after_saving(): void
    {
        $user = $this->vendorWithApplication();

        $user->vendorApplication()->update([
            'payment_method' => 'bank',
            'bank_name' => 'GCB BANK',
            'bank_account_name' => 'Ama Mensah',
            'bank_account_number' => '0123456789',
        ]);

        $this->actingAs($user)
            ->from(route('vendor.dashboard'))
            ->put(route('vendor.payment-details.update'), [
                'payment_method' => 'mobile_money',
                'mobile_money_provider' => 'MTN MoMo',
                'mobile_money_name' => 'Ama Mensah',
                'mobile_money_number' => '0241234567',
            ])
            ->assertRedirect(route('vendor.dashboard'))
            ->assertSessionHas('error');

        $application = $user->fresh()->vendorApplication;

        $this->assertSame('bank', $application->payment_method);
        $this->assertSame('GCB BANK', $application->bank_name);
        $this->assertNull($application->mobile_money_provider);
    }

    private function vendorWithApplication(): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        return $user;
    }
}
