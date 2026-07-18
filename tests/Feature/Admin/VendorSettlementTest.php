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
use App\Services\VendorSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_payout_is_due_only_after_delivery(): void
    {
        $vendor = $this->createVendor();
        $order = $this->createPaidOrder($vendor);

        $this->assertFalse($order->vendorPayoutIsDue());

        VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::Delivered,
            'delivered_at' => now(),
            'fulfilled_at' => now(),
        ]);

        $this->assertTrue($order->fresh(['vendorFulfillments'])->vendorPayoutIsDue());
        $this->assertTrue(app(VendorSettlementService::class)->markPaid($order->fresh(['vendorFulfillments'])));
        $this->assertFalse($order->fresh(['vendorFulfillments'])->vendorPayoutIsDue());
        $this->assertTrue($order->fresh()->isVendorPaid());
    }

    public function test_platform_earnings_shows_vendor_due_and_paid(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendor = $this->createVendor();
        $dueOrder = $this->createPaidOrder($vendor, 'LH-V-DUE', 50000);
        $paidOrder = $this->createPaidOrder($vendor, 'LH-V-PAID', 30000);

        foreach ([$dueOrder, $paidOrder] as $order) {
            VendorOrderFulfillment::create([
                'order_id' => $order->id,
                'vendor_user_id' => $vendor->id,
                'status' => VendorFulfillmentStatus::Delivered,
                'delivered_at' => now(),
                'fulfilled_at' => now(),
            ]);
        }

        $paidOrder->update(['vendor_paid_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/platform-earnings')
            ->assertOk()
            ->assertSee('Due to vendors')
            ->assertSee('Paid to vendors')
            ->assertSee('GHS 400.00') // 80% of 500
            ->assertSee('GHS 240.00'); // 80% of 300
    }

    private function createVendor(): User
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

        return $vendor;
    }

    private function createPaidOrder(User $vendor, string $orderNumber = 'LH-V-1', int $lineTotalCents = 50000): Order
    {
        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Stroller',
            'sku' => 'SKU-'.$orderNumber,
            'category' => 'toys_development',
            'price_cents' => $lineTotalCents,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
            'image_urls' => [
                'https://example.com/a.jpg',
                'https://example.com/b.jpg',
                'https://example.com/c.jpg',
            ],
        ]);

        $order = Order::create([
            'order_number' => $orderNumber,
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'REF-'.$orderNumber,
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => $lineTotalCents,
            'shipping_cents' => 0,
            'total_cents' => $lineTotalCents,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'unit_price_cents' => $lineTotalCents,
            'quantity' => 1,
            'line_total_cents' => $lineTotalCents,
        ]);

        return $order->fresh(['items', 'vendorFulfillments']);
    }
}
