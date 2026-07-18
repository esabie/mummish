<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_courier_paid_moves_fee_from_due_to_paid(): void
    {
        $order = $this->createPaidOrderWithShipping(1500);

        $this->assertTrue($order->courierFeeIsDue());
        $this->assertTrue(app(CourierSettlementService::class)->markPaid($order));

        $order->refresh();
        $this->assertFalse($order->courierFeeIsDue());
        $this->assertTrue($order->isCourierPaid());
        $this->assertNotNull($order->courier_paid_at);
    }

    public function test_bulk_mark_courier_paid_only_updates_due_orders(): void
    {
        $due = $this->createPaidOrderWithShipping(1500, 'LH-DUE-1');
        $alreadyPaid = $this->createPaidOrderWithShipping(2000, 'LH-PAID-1');
        $alreadyPaid->update(['courier_paid_at' => now()]);
        $noShipping = $this->createPaidOrderWithShipping(0, 'LH-NONE-1');

        $count = app(CourierSettlementService::class)->markManyPaid(collect([
            $due,
            $alreadyPaid->fresh(),
            $noShipping,
        ]));

        $this->assertSame(1, $count);
        $this->assertTrue($due->fresh()->isCourierPaid());
        $this->assertTrue($alreadyPaid->fresh()->isCourierPaid());
        $this->assertNull($noShipping->fresh()->courier_paid_at);
    }

    public function test_admin_can_see_courier_due_filter_on_orders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createPaidOrderWithShipping(1500);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Courier');
    }

    public function test_platform_earnings_shows_due_and_paid_courier_totals(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $due = $this->createPaidOrderWithShipping(1500, 'LH-EARN-DUE');
        $paid = $this->createPaidOrderWithShipping(2000, 'LH-EARN-PAID');
        $paid->update(['courier_paid_at' => now()]);

        // Need line items for platform earnings query (paid order items).
        $due->items()->create([
            'vendor_user_id' => User::factory()->create(['role' => UserRole::Vendor])->id,
            'product_title' => 'Item A',
            'unit_price_cents' => 1000,
            'quantity' => 1,
            'line_total_cents' => 1000,
        ]);
        $paid->items()->create([
            'vendor_user_id' => User::factory()->create(['role' => UserRole::Vendor])->id,
            'product_title' => 'Item B',
            'unit_price_cents' => 1000,
            'quantity' => 1,
            'line_total_cents' => 1000,
        ]);

        $this->actingAs($admin)
            ->get('/admin/platform-earnings')
            ->assertOk()
            ->assertSee('Due to courier')
            ->assertSee('Paid to courier')
            ->assertSee('GHS 15.00')
            ->assertSee('GHS 20.00')
            ->assertSee('GHS 35.00');
    }

    private function createPaidOrderWithShipping(int $shippingCents, string $orderNumber = 'LH-COURIER-1'): Order
    {
        return Order::create([
            'order_number' => $orderNumber,
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'REF-'.$orderNumber,
            'customer_name' => 'Buyer Name',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 5000,
            'shipping_cents' => $shippingCents,
            'total_cents' => 5000 + $shippingCents,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);
    }
}
