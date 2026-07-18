<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorApplicationStatusSms;
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

    private function createPendingApplication(User $vendor): VendorApplication
    {
        return VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);
    }
}
