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

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_orders_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk();
    }

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $order = $this->createOrder();

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk();
    }

    public function test_admin_order_detail_shows_vendor_for_line_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $order = $this->createOrder();

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Little Knot');
    }

    public function test_non_admin_cannot_access_orders(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/orders')
            ->assertForbidden();
    }

    private function createOrder(): Order
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

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'product_sku' => $product->sku,
            'unit_price_cents' => 2500,
            'quantity' => 1,
            'line_total_cents' => 2500,
        ]);

        return $order;
    }
}
