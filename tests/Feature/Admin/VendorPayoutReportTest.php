<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\VendorPayoutReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPayoutReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_vendor_payout_report(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->seedPaidOrderLineItem();

        $this->actingAs($admin)
            ->get('/admin/vendor-payout-report')
            ->assertOk()
            ->assertSee('Little Knot')
            ->assertSee('LH-10001');
    }

    public function test_non_admin_cannot_access_vendor_payout_report(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/vendor-payout-report')
            ->assertForbidden();
    }

    public function test_report_excludes_unpaid_orders(): void
    {
        $vendor = $this->createVendor('Little Knot');
        $product = $this->createProduct($vendor);

        $paidOrder = $this->createOrder('LH-PAID', PaymentStatus::Paid, now()->subDay());
        $this->createOrderItem($paidOrder, $product, $vendor);

        $pendingOrder = $this->createOrder('LH-PENDING', PaymentStatus::Pending, null);
        $this->createOrderItem($pendingOrder, $product, $vendor);

        $items = app(VendorPayoutReportService::class)->paidOrderItemsQuery()->get();

        $this->assertCount(1, $items);
        $this->assertSame('LH-PAID', $items->first()->order->order_number);
    }

    public function test_service_filters_by_date_range(): void
    {
        $vendor = $this->createVendor('Little Knot');
        $product = $this->createProduct($vendor);

        $oldOrder = $this->createOrder('LH-OLD', PaymentStatus::Paid, now()->subDays(10));
        $this->createOrderItem($oldOrder, $product, $vendor);

        $recentOrder = $this->createOrder('LH-NEW', PaymentStatus::Paid, now()->subDay());
        $this->createOrderItem($recentOrder, $product, $vendor);

        $items = app(VendorPayoutReportService::class)
            ->paidOrderItemsQuery()
            ->whereHas('order', fn ($query) => $query->whereDate('paid_at', '>=', now()->subDays(3)->toDateString()))
            ->get();

        $this->assertCount(1, $items);
        $this->assertSame('LH-NEW', $items->first()->order->order_number);
    }

    public function test_service_filters_by_shop(): void
    {
        $vendorA = $this->createVendor('Shop Alpha');
        $vendorB = $this->createVendor('Shop Beta');
        $productA = $this->createProduct($vendorA, 'SKU-A');
        $productB = $this->createProduct($vendorB, 'SKU-B');

        $order = $this->createOrder('LH-MULTI', PaymentStatus::Paid, now());
        $this->createOrderItem($order, $productA, $vendorA);
        $this->createOrderItem($order, $productB, $vendorB, lineTotalCents: 1800);

        $items = app(VendorPayoutReportService::class)
            ->paidOrderItemsQuery()
            ->where('vendor_user_id', $vendorA->id)
            ->get();

        $this->assertCount(1, $items);
        $this->assertSame('SKU-A', $items->first()->product_sku);
    }

    public function test_csv_export_includes_line_items_and_vendor_summary(): void
    {
        $vendor = $this->createVendor('Little Knot');
        $product = $this->createProduct($vendor);
        $order = $this->createOrder('LH-10001', PaymentStatus::Paid, now());
        $this->createOrderItem($order, $product, $vendor);

        $items = app(VendorPayoutReportService::class)->paidOrderItemsQuery()->get();
        $response = app(VendorPayoutReportService::class)->csvDownloadResponse($items);

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Order #', $csv);
        $this->assertStringContainsString('LH-10001', $csv);
        $this->assertStringContainsString('Little Knot', $csv);
        $this->assertStringContainsString('Vendor summary', $csv);
        $this->assertStringContainsString('Payout due (GHS)', $csv);
        $this->assertStringContainsString('25.00', $csv);
    }

    public function test_vendor_totals_aggregate_line_items(): void
    {
        $vendor = $this->createVendor('Little Knot');
        $product = $this->createProduct($vendor);

        $orderOne = $this->createOrder('LH-1', PaymentStatus::Paid, now());
        $this->createOrderItem($orderOne, $product, $vendor, quantity: 2, lineTotalCents: 5000);

        $orderTwo = $this->createOrder('LH-2', PaymentStatus::Paid, now());
        $this->createOrderItem($orderTwo, $product, $vendor);

        $items = app(VendorPayoutReportService::class)->paidOrderItemsQuery()->get();
        $totals = app(VendorPayoutReportService::class)->vendorTotals($items);

        $this->assertCount(1, $totals);
        $this->assertSame('Little Knot', $totals->first()['shop_name']);
        $this->assertSame(3, $totals->first()['quantity']);
        $this->assertSame(7500, $totals->first()['total_cents']);
        $this->assertSame(2, $totals->first()['order_count']);
    }

    private function seedPaidOrderLineItem(): void
    {
        $vendor = $this->createVendor('Little Knot');
        $product = $this->createProduct($vendor);
        $order = $this->createOrder('LH-10001', PaymentStatus::Paid, now());
        $this->createOrderItem($order, $product, $vendor);
    }

    private function createVendor(string $shopName): User
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => $shopName,
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $vendor;
    }

    private function createProduct(User $vendor, string $sku = 'LH-001'): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => $sku,
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
        ]);
    }

    private function createOrder(string $orderNumber, PaymentStatus $paymentStatus, $paidAt): Order
    {
        return Order::create([
            'order_number' => $orderNumber,
            'status' => $paymentStatus === PaymentStatus::Paid ? OrderStatus::Paid : OrderStatus::PendingPayment,
            'payment_status' => $paymentStatus,
            'paystack_reference' => 'REF-'.$orderNumber,
            'customer_name' => 'Buyer Name',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 2500,
            'shipping_cents' => 1500,
            'total_cents' => 4000,
            'paid_at' => $paidAt,
        ]);
    }

    private function createOrderItem(
        Order $order,
        Product $product,
        User $vendor,
        int $quantity = 1,
        ?int $lineTotalCents = null,
    ): OrderItem {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'product_sku' => $product->sku,
            'unit_price_cents' => 2500,
            'quantity' => $quantity,
            'line_total_cents' => $lineTotalCents ?? (2500 * $quantity),
        ]);
    }
}
