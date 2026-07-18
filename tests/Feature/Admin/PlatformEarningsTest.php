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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformEarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_platform_earnings_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->seedPaidOrderLineItem();

        $this->actingAs($admin)
            ->get('/admin/platform-earnings')
            ->assertOk()
            ->assertSee('Platform earnings')
            ->assertSee('Mummish commission')
            ->assertSee('Delivery fees')
            ->assertSee('Due to courier')
            ->assertSee('Paid to courier')
            ->assertSee('Merchandise pool')
            ->assertSee('Little Knot')
            ->assertSee('GHS 50.00')
            ->assertSee('GHS 10.00')
            ->assertSee('GHS 40.00')
            ->assertSee('GHS 15.00')
            ->assertSee('GHS 65.00');
    }

    public function test_non_admin_cannot_access_platform_earnings_page(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/platform-earnings')
            ->assertForbidden();
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
            'price_cents' => 5000,
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
            'subtotal_cents' => 5000,
            'shipping_cents' => 1500,
            'total_cents' => 6500,
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
            'unit_price_cents' => 5000,
            'quantity' => $quantity,
            'line_total_cents' => $lineTotalCents ?? (5000 * $quantity),
        ]);
    }
}
