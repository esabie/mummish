<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorFulfillmentStatus;
use App\Jobs\SendOrderDeliveredSms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorOrderFulfillment;
use App\Services\OrderTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_track_order_form(): void
    {
        $this->get(route('orders.track'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Orders/Track'));
    }

    public function test_lookup_with_valid_details_shows_tracking_page(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');

        $this->post(route('orders.track.lookup'), [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
        ])
            ->assertRedirect(route('orders.track.show', $order));

        $this->get(route('orders.track.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Show')
                ->where('order.order_number', $order->order_number)
                ->where('order.current_step_label', 'Preparing your order'));
    }

    public function test_lookup_with_wrong_email_returns_generic_error(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');

        $this->from(route('orders.track'))
            ->post(route('orders.track.lookup'), [
                'order_number' => $order->order_number,
                'customer_email' => 'wrong@example.com',
            ])
            ->assertRedirect(route('orders.track'))
            ->assertSessionHas('error');
    }

    public function test_show_requires_verification_for_guests(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');

        $this->get(route('orders.track.show', $order))
            ->assertRedirect(route('orders.track'))
            ->assertSessionHas('error');
    }

    public function test_logged_in_owner_can_view_without_lookup(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->createPaidOrder('buyer@example.com', $buyer);

        $this->actingAs($buyer)
            ->get(route('orders.track.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Orders/Show'));
    }

    public function test_logged_in_non_owner_cannot_view_order_tracking(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('orders.track.show', $order))
            ->assertRedirect(route('orders.track'))
            ->assertSessionHas('error');
    }

    public function test_timeline_reflects_fulfillment_status(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');
        $vendor = $order->items->first()->vendor;

        VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::ShippedToCustomer,
            'ready_for_pickup_at' => now()->subDays(3),
            'picked_up_at' => now()->subDays(2),
            'received_at_warehouse_at' => now()->subDay(),
            'shipped_to_customer_at' => now(),
            'fulfilled_at' => now(),
        ]);

        $payload = app(OrderTrackingService::class)->formatTrackingPayload($order->fresh());

        $this->assertSame('Out for delivery', $payload['current_step_label']);
        $this->assertCount(4, $payload['timeline']);
        $this->assertSame('Preparing your order', $payload['timeline'][1]['label']);
        $this->assertSame('complete', $payload['timeline'][0]['status']);
        $this->assertSame('complete', $payload['timeline'][1]['status']);
        $this->assertSame('current', $payload['timeline'][2]['status']);
        $this->assertTrue($payload['can_confirm_receipt']);
        $this->assertFalse($payload['is_delivered']);
    }

    public function test_customer_can_confirm_receipt_and_release_escrow(): void
    {
        Bus::fake([SendOrderDeliveredSms::class]);

        $order = $this->createPaidOrder('buyer@example.com');
        $vendor = $order->items->first()->vendor;

        VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::ShippedToCustomer,
            'ready_for_pickup_at' => now()->subDays(3),
            'picked_up_at' => now()->subDays(2),
            'received_at_warehouse_at' => now()->subDay(),
            'shipped_to_customer_at' => now(),
            'fulfilled_at' => now()->subDays(3),
        ]);

        $this->withSession(['verified_order_ids' => [$order->id]])
            ->post(route('orders.track.received', $order))
            ->assertRedirect(route('orders.track.show', $order))
            ->assertSessionHas('status');

        $order->refresh();
        $fulfillment = $order->vendorFulfillments()->first();

        $this->assertSame(VendorFulfillmentStatus::Delivered, $fulfillment->status);
        $this->assertNotNull($fulfillment->delivered_at);

        Bus::assertDispatched(SendOrderDeliveredSms::class, fn (SendOrderDeliveredSms $job) => $job->orderId === $order->id);

        $summary = app(\App\Services\VendorEarningsService::class)->dashboardSummary($vendor);
        $this->assertSame(0, $summary['escrow']['gross_cents']);
        $this->assertGreaterThan(0, $summary['wallet']['payout_cents']);

        $this->withSession(['verified_order_ids' => [$order->id]])
            ->get(route('orders.track.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.current_step_label', 'Delivered')
                ->where('order.can_confirm_receipt', false)
                ->where('order.is_delivered', true));
    }

    public function test_customer_cannot_confirm_receipt_before_shipped(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');

        $this->withSession(['verified_order_ids' => [$order->id]])
            ->post(route('orders.track.received', $order))
            ->assertRedirect(route('orders.track.show', $order))
            ->assertSessionHas('error');
    }

    public function test_mid_pipeline_statuses_still_show_as_preparing(): void
    {
        $order = $this->createPaidOrder('buyer@example.com');
        $vendor = $order->items->first()->vendor;

        VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::ReceivedAtWarehouse,
            'ready_for_pickup_at' => now()->subDays(2),
            'picked_up_at' => now()->subDay(),
            'received_at_warehouse_at' => now(),
            'fulfilled_at' => now()->subDays(2),
        ]);

        $payload = app(OrderTrackingService::class)->formatTrackingPayload($order->fresh());

        $this->assertSame('Preparing your order', $payload['current_step_label']);
        $this->assertSame('current', $payload['timeline'][1]['status']);
        $this->assertSame('upcoming', $payload['timeline'][2]['status']);
    }

    public function test_unpaid_orders_cannot_be_tracked(): void
    {
        $vendor = $this->createVendor();
        $product = $this->createProduct($vendor);
        $order = Order::create([
            'order_number' => 'LH-PENDING-001',
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'paystack_reference' => 'REF-PENDING',
            'customer_name' => 'Buyer Name',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 2500,
            'shipping_cents' => 1500,
            'total_cents' => 4000,
        ]);
        $this->createOrderItem($order, $product, $vendor);

        $found = app(OrderTrackingService::class)->findForLookup($order->order_number, 'buyer@example.com');

        $this->assertNull($found);
    }

    private function createPaidOrder(string $email, ?User $buyer = null): Order
    {
        $vendor = $this->createVendor();
        $product = $this->createProduct($vendor);
        $order = Order::create([
            'user_id' => $buyer?->id,
            'order_number' => 'LH-TRACK-'.uniqid(),
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'REF-'.uniqid(),
            'customer_name' => 'Buyer Name',
            'customer_email' => $email,
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 2500,
            'shipping_cents' => 1500,
            'total_cents' => 4000,
            'paid_at' => now(),
        ]);
        $this->createOrderItem($order, $product, $vendor);

        return $order->fresh(['items.vendor']);
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

    private function createProduct(User $vendor): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => 'LH-001',
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
        ]);
    }

    private function createOrderItem(Order $order, Product $product, User $vendor): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'product_sku' => $product->sku,
            'unit_price_cents' => 2500,
            'quantity' => 1,
            'line_total_cents' => 2500,
        ]);
    }
}
