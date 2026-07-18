<?php

namespace Tests\Feature\Vendor;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendAdminVendorOrderReadyForPickupSms;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorOrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class VendorOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_orders_hide_customer_phone(): void
    {
        $vendor = $this->vendorWithApplication();
        $product = $this->createProduct($vendor);

        $order = $this->createPaidOrder($vendor, $product, phone: '0249998877');

        $response = $this->actingAs($vendor)->get(route('vendor.orders.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Vendor/Orders/Index')
            ->has('orders', 1)
            ->where('orders.0.order_number', $order->order_number)
            ->where('orders.0.is_ready_for_pickup', false)
            ->missing('orders.0.customer_phone')
            ->missing('orders.0.customer_name')
            ->missing('orders.0.shipping_city')
            ->missing('orders.0.shipping_region')
            ->where('counts.new', 1)
            ->where('counts.ready', 0));
    }

    public function test_vendor_can_mark_order_ready_for_pickup(): void
    {
        Bus::fake();

        $vendor = $this->vendorWithApplication();
        $product = $this->createProduct($vendor);
        $order = $this->createPaidOrder($vendor, $product);

        $response = $this->actingAs($vendor)->post(route('vendor.orders.fulfill', $order));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vendor_order_fulfillments', [
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => 'ready_for_pickup',
        ]);

        $this->actingAs($vendor)->get(route('vendor.orders.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.0.is_ready_for_pickup', true)
                ->where('counts.new', 0)
                ->where('counts.ready', 1));

        Bus::assertDispatched(SendAdminVendorOrderReadyForPickupSms::class);
    }

    public function test_vendor_cannot_fulfill_another_vendors_order(): void
    {
        $vendor = $this->vendorWithApplication();
        $otherVendor = $this->vendorWithApplication('Other Shop');
        $product = $this->createProduct($otherVendor);
        $order = $this->createPaidOrder($otherVendor, $product);

        $this->actingAs($vendor)
            ->post(route('vendor.orders.fulfill', $order))
            ->assertForbidden();

        $this->assertDatabaseCount('vendor_order_fulfillments', 0);
    }

    private function vendorWithApplication(string $shopName = 'Test Shop'): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Vendor,
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Vendor',
            'shop_name' => $shopName,
            'business_email' => $user->email,
            'phone' => '0240000000',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }

    private function createProduct(User $vendor): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Vendor Product',
            'sku' => 'LH-V-001',
            'category' => 'toys_development',
            'brand' => 'Fisher-Price',
            'price_cents' => 1500,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => ['https://example.com/product.jpg'],
            'allows_customization' => false,
        ]);
    }

    private function createPaidOrder(User $vendor, Product $product, ?string $phone = null): Order
    {
        $order = Order::create([
            'order_number' => 'LH-TEST-'.fake()->unique()->numerify('###'),
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-REF-'.fake()->unique()->numerify('###'),
            'customer_name' => 'Kofi Mensah',
            'customer_email' => 'kofi@example.com',
            'customer_phone' => $phone ?? '0201112233',
            'shipping_address_line1' => '5 Ring Road',
            'shipping_city' => 'Kumasi',
            'shipping_region' => 'Ashanti',
            'subtotal_cents' => 1500,
            'shipping_cents' => 0,
            'total_cents' => 1500,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'unit_price_cents' => 1500,
            'quantity' => 1,
            'line_total_cents' => 1500,
        ]);

        return $order;
    }
}
