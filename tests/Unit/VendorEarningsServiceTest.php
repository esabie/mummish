<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\PromoCostBearer;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorFulfillmentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorOrderFulfillment;
use App\Services\VendorEarningsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorEarningsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_amount_applies_twenty_percent_commission(): void
    {
        $service = new VendorEarningsService;

        $split = $service->splitAmount(50000);

        $this->assertSame(50000, $split['gross_cents']);
        $this->assertSame(10000, $split['commission_cents']);
        $this->assertSame(40000, $split['payout_cents']);
        $this->assertSame(0, $split['discount_cents']);
    }

    public function test_split_amount_mummish_absorbs_promo_discount(): void
    {
        $service = new VendorEarningsService;

        // GHS 500 gross, GHS 50 discount, 20% commission (GHS 100)
        $split = $service->splitAmount(50000, 5000, PromoCostBearer::Mummish);

        $this->assertSame(50000, $split['gross_cents']);
        $this->assertSame(5000, $split['commission_cents']);
        $this->assertSame(40000, $split['payout_cents']);
        $this->assertSame(5000, $split['discount_cents']);
        $this->assertSame(45000, $split['commission_cents'] + $split['payout_cents']);
    }

    public function test_split_amount_vendor_absorbs_promo_discount(): void
    {
        $service = new VendorEarningsService;

        $split = $service->splitAmount(50000, 5000, PromoCostBearer::Vendor);

        $this->assertSame(10000, $split['commission_cents']);
        $this->assertSame(35000, $split['payout_cents']);
        $this->assertSame(45000, $split['commission_cents'] + $split['payout_cents']);
    }

    public function test_split_amount_both_split_promo_discount_evenly(): void
    {
        $service = new VendorEarningsService;

        $split = $service->splitAmount(50000, 5000, PromoCostBearer::Both);

        $this->assertSame(7500, $split['commission_cents']);
        $this->assertSame(37500, $split['payout_cents']);
        $this->assertSame(45000, $split['commission_cents'] + $split['payout_cents']);
    }

    public function test_dashboard_summary_applies_vendor_borne_promo(): void
    {
        $vendor = $this->createVendor();
        $product = $this->createProduct($vendor, 50000);

        $this->createPaidOrder(
            $vendor,
            $product,
            50000,
            'LH-PROMO-001',
            shippingCents: 0,
            discountCents: 5000,
            promoCostBearer: PromoCostBearer::Vendor,
        );

        $summary = (new VendorEarningsService)->dashboardSummary($vendor);

        $this->assertSame(50000, $summary['totals']['gross_cents']);
        $this->assertSame(10000, $summary['totals']['commission_cents']);
        $this->assertSame(35000, $summary['totals']['payout_cents']);
    }

    public function test_dashboard_summary_segregates_commission_and_payout(): void
    {
        $vendor = $this->createVendor();
        $product = $this->createProduct($vendor, 50000);

        $order = $this->createPaidOrder($vendor, $product, 50000);

        $summary = (new VendorEarningsService)->dashboardSummary($vendor);

        $this->assertSame(20, $summary['commission_percent']);
        $this->assertSame(50000, $summary['totals']['gross_cents']);
        $this->assertSame(10000, $summary['totals']['commission_cents']);
        $this->assertSame(40000, $summary['totals']['payout_cents']);
        $this->assertSame('GHS 500.00', $summary['totals']['formatted_gross']);
        $this->assertSame('GHS 100.00', $summary['totals']['formatted_commission']);
        $this->assertSame('GHS 400.00', $summary['totals']['formatted_payout']);
        $this->assertSame(50000, $summary['escrow']['gross_cents']);
        $this->assertSame(0, $summary['wallet']['gross_cents']);
        $this->assertSame('escrow', $summary['recent_sales'][0]['status']);
    }

    public function test_platform_summary_aggregates_commission_across_vendors(): void
    {
        $vendorA = $this->createVendor('Shop Alpha');
        $vendorB = $this->createVendor('Shop Beta');
        $productA = $this->createProduct($vendorA, 50000, 'SKU-A');
        $productB = $this->createProduct($vendorB, 30000, 'SKU-B');

        $this->createPaidOrder($vendorA, $productA, 50000, 'LH-A', shippingCents: 3500);
        $this->createPaidOrder($vendorB, $productB, 30000, 'LH-B', shippingCents: 4500);

        $items = \App\Models\OrderItem::query()->with(['order', 'vendor.vendorApplication'])->get();
        $summary = (new VendorEarningsService)->platformSummary($items);

        $this->assertSame(80000, $summary['totals']['gross_cents']);
        $this->assertSame(16000, $summary['totals']['commission_cents']);
        $this->assertSame(64000, $summary['totals']['payout_cents']);
        $this->assertSame(8000, $summary['delivery']['shipping_cents']);
        $this->assertSame(2, $summary['delivery']['order_count']);
        $this->assertSame(8000, $summary['delivery']['due_cents']);
        $this->assertSame(2, $summary['delivery']['due_order_count']);
        $this->assertSame(0, $summary['delivery']['paid_cents']);
        $this->assertSame(88000, $summary['collected']['total_cents']);
        $this->assertSame('GHS 80.00', $summary['delivery']['formatted_shipping']);
        $this->assertSame('GHS 80.00', $summary['delivery']['formatted_due']);
        $this->assertSame('GHS 0.00', $summary['delivery']['formatted_paid']);
        $this->assertCount(2, $summary['vendor_breakdown']);
        $this->assertSame('Shop Alpha', $summary['vendor_breakdown'][0]['shop_name']);
        $this->assertSame(10000, $summary['vendor_breakdown'][0]['commission_cents']);
        $this->assertSame(40000, $summary['vendor_breakdown'][0]['payout_cents']);
    }

    public function test_dashboard_moves_earnings_to_wallet_after_delivery(): void
    {
        $vendor = $this->createVendor();
        $product = $this->createProduct($vendor, 50000);
        $order = $this->createPaidOrder($vendor, $product, 50000);

        VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::Delivered,
            'shipped_to_customer_at' => now()->subHour(),
            'delivered_at' => now(),
            'fulfilled_at' => now(),
        ]);

        $summary = (new VendorEarningsService)->dashboardSummary($vendor);

        $this->assertSame(0, $summary['escrow']['gross_cents']);
        $this->assertSame(50000, $summary['wallet']['gross_cents']);
        $this->assertSame(40000, $summary['wallet']['payout_cents']);
        $this->assertSame(40000, $summary['wallet_due']['payout_cents']);
        $this->assertSame(0, $summary['wallet_settled']['payout_cents']);
        $this->assertSame('released', $summary['recent_sales'][0]['status']);
    }

    private function createVendor(string $shopName = 'Little Knot'): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => $shopName,
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }

    private function createProduct(User $vendor, int $priceCents, string $sku = 'STR-01'): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Fairly New Stroller',
            'sku' => $sku,
            'category' => 'toys_development',
            'price_cents' => $priceCents,
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
    }

    private function createPaidOrder(
        User $vendor,
        Product $product,
        int $lineTotalCents,
        string $orderNumber = 'LH-TEST-001',
        int $shippingCents = 0,
        int $discountCents = 0,
        ?PromoCostBearer $promoCostBearer = null,
    ): Order {
        $order = Order::create([
            'order_number' => $orderNumber,
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-REF-'.$orderNumber,
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => $lineTotalCents,
            'shipping_cents' => $shippingCents,
            'discount_cents' => $discountCents,
            'promo_code' => $discountCents > 0 ? 'TESTPROMO' : null,
            'promo_cost_bearer' => $promoCostBearer,
            'total_cents' => $lineTotalCents - $discountCents + $shippingCents,
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

        return $order;
    }
}
