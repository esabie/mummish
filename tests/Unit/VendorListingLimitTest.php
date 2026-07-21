<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\VendorListingLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorListingLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_vendor_is_limited_to_two_listings(): void
    {
        config(['marketplace.max_listings_while_pending' => 2]);

        $user = $this->pendingVendor();

        $limit = app(VendorListingLimit::class);

        $this->assertSame(2, $limit->maxListingsFor($user));
        $this->assertTrue($limit->canAddListing($user));

        Product::create(['user_id' => $user->id, 'title' => 'One', 'price_cents' => 1000]);
        Product::create(['user_id' => $user->id, 'title' => 'Two', 'price_cents' => 2000]);

        $this->assertFalse($limit->canAddListing($user));
        $this->assertSame(0, $limit->remainingListings($user));
    }

    public function test_approved_vendor_has_unlimited_listings(): void
    {
        $user = $this->pendingVendor();
        $user->vendorApplication->update(['status' => VendorApplicationStatus::Approved]);

        $limit = app(VendorListingLimit::class);

        $this->assertNull($limit->maxListingsFor($user));
        $this->assertTrue($limit->canAddListing($user));

        for ($i = 0; $i < 5; $i++) {
            Product::create(['user_id' => $user->id, 'title' => "Item {$i}", 'price_cents' => 1000]);
        }

        $this->assertTrue($limit->canAddListing($user));
    }

    public function test_rejected_vendor_cannot_list(): void
    {
        $user = $this->pendingVendor();
        $user->vendorApplication->update([
            'status' => VendorApplicationStatus::Rejected,
            'rejection_reason' => 'Incomplete documents',
        ]);

        $limit = app(VendorListingLimit::class);

        $this->assertSame(0, $limit->maxListingsFor($user));
        $this->assertFalse($limit->canAddListing($user));
    }

    public function test_closed_vendor_cannot_list(): void
    {
        $user = $this->pendingVendor();
        $user->vendorApplication->update([
            'status' => VendorApplicationStatus::Closed,
            'rejection_reason' => 'Shop closed by admin',
        ]);

        $limit = app(VendorListingLimit::class);

        $this->assertSame(0, $limit->maxListingsFor($user));
        $this->assertFalse($limit->canAddListing($user));
        $this->assertSame(
            'Your shop has been closed. You cannot list products.',
            $limit->limitMessage($user),
        );
    }

    private function pendingVendor(): User
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

        return $user->fresh();
    }
}
