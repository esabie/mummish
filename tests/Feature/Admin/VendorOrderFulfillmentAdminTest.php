<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorFulfillmentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorOrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOrderFulfillmentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pickup_pipeline_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->seedReadyForPickupRecord();

        $this->actingAs($admin)
            ->get('/admin/vendor-order-fulfillments')
            ->assertOk();
    }

    public function test_admin_can_view_pickup_pipeline_record(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $record = $this->seedReadyForPickupRecord();

        $this->actingAs($admin)
            ->get("/admin/vendor-order-fulfillments/{$record->id}")
            ->assertOk();
    }

    public function test_non_admin_cannot_access_pickup_pipeline(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/vendor-order-fulfillments')
            ->assertForbidden();
    }

    public function test_pipeline_status_can_progress_to_shipped(): void
    {
        $record = $this->seedReadyForPickupRecord();

        $record->update([
            'status' => VendorFulfillmentStatus::PickedUp,
            'picked_up_at' => now(),
        ]);
        $record->refresh();
        $this->assertSame(VendorFulfillmentStatus::PickedUp, $record->status);

        $record->update([
            'status' => VendorFulfillmentStatus::ReceivedAtWarehouse,
            'received_at_warehouse_at' => now(),
        ]);
        $record->refresh();
        $this->assertSame(VendorFulfillmentStatus::ReceivedAtWarehouse, $record->status);

        $record->update([
            'status' => VendorFulfillmentStatus::ShippedToCustomer,
            'shipped_to_customer_at' => now(),
        ]);
        $record->refresh();
        $this->assertSame(VendorFulfillmentStatus::ShippedToCustomer, $record->status);
    }

    private function seedReadyForPickupRecord(): VendorOrderFulfillment
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => 'LH-001',
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
        ]);

        $order = Order::create([
            'order_number' => 'LH-10001',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-REF-10001',
            'customer_name' => 'Buyer Name',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 2500,
            'shipping_cents' => 1500,
            'total_cents' => 4000,
            'paid_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'product_sku' => $product->sku,
            'unit_price_cents' => 2500,
            'quantity' => 1,
            'line_total_cents' => 2500,
        ]);

        return VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::ReadyForPickup,
            'ready_for_pickup_at' => now(),
            'fulfilled_at' => now(),
        ]);
    }
}
