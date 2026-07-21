<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorApplicationStatusSms;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\VendorApplicationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class VendorApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_application(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = $this->createPendingApplication($vendor);

        app(VendorApplicationReviewService::class)->approve($application, $admin);

        $application->refresh();
        $this->assertSame(VendorApplicationStatus::Approved, $application->status);
        $this->assertSame('little-knot', $application->shop_slug);
        $this->assertNull($application->rejection_reason);
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame($admin->id, $application->reviewed_by_user_id);

        Bus::assertDispatched(SendVendorApplicationStatusSms::class);
    }

    public function test_admin_can_reject_pending_application_with_reason(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = $this->createPendingApplication($vendor);

        app(VendorApplicationReviewService::class)->reject(
            $application,
            $admin,
            'Shop category not supported yet.',
        );

        $application->refresh();
        $this->assertSame(VendorApplicationStatus::Rejected, $application->status);
        $this->assertSame('Shop category not supported yet.', $application->rejection_reason);

        Bus::assertDispatched(SendVendorApplicationStatusSms::class);
    }

    public function test_cannot_approve_already_reviewed_application(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = $this->createPendingApplication($vendor);
        $application->update(['status' => VendorApplicationStatus::Approved]);

        $this->expectException(\InvalidArgumentException::class);

        app(VendorApplicationReviewService::class)->approve($application, $admin);
    }

    public function test_admin_can_close_down_approved_vendor_and_delete_products(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $otherVendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = $this->createPendingApplication($vendor);
        app(VendorApplicationReviewService::class)->approve($application, $admin);

        $otherApplication = $this->createPendingApplication($otherVendor, 'Other Shop');
        app(VendorApplicationReviewService::class)->approve($otherApplication, $admin);

        $keepProduct = Product::create([
            'user_id' => $otherVendor->id,
            'title' => 'Keep Me',
            'sku' => 'KEEP-1',
            'category' => 'toys_development',
            'price_cents' => 1500,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Blocks',
            'sku' => 'LH-001',
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
        ]);
        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Draft Rattle',
            'sku' => 'LH-002',
            'category' => 'toys_development',
            'price_cents' => 1200,
            'stock_quantity' => 3,
            'status' => ProductStatus::Draft,
        ]);

        $deleted = app(VendorApplicationReviewService::class)->closeDown(
            $application->fresh(),
            $admin,
            'Policy violations.',
        );

        $this->assertSame(2, $deleted);

        $application->refresh();
        $this->assertSame(VendorApplicationStatus::Closed, $application->status);
        $this->assertSame('Policy violations.', $application->rejection_reason);
        $this->assertSame($admin->id, $application->reviewed_by_user_id);

        $this->assertSame(0, Product::query()->where('user_id', $vendor->id)->count());
        $this->assertTrue(Product::query()->whereKey($keepProduct->id)->exists());
        $this->assertTrue(
            Product::query()->visibleInShop()->whereKey($keepProduct->id)->exists()
        );

        Bus::assertDispatched(SendVendorApplicationStatusSms::class);
    }

    public function test_cannot_close_down_pending_or_already_closed_vendor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = $this->createPendingApplication($vendor);

        try {
            app(VendorApplicationReviewService::class)->closeDown($application, $admin);
            $this->fail('Expected InvalidArgumentException for pending vendor.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Only approved vendors', $e->getMessage());
        }

        $application->update(['status' => VendorApplicationStatus::Closed]);

        $this->expectException(\InvalidArgumentException::class);
        app(VendorApplicationReviewService::class)->closeDown($application->fresh(), $admin);
    }

    public function test_non_admin_cannot_access_filament_panel(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    private function createPendingApplication(User $vendor, string $shopName = 'Little Knot'): VendorApplication
    {
        return VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => $shopName,
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);
    }
}
