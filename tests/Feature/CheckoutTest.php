<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendOrderPaidSms;
use App\Jobs\SendVendorNewOrderSms;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_loads_when_paystack_configured(): void
    {
        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Checkout/Index')
            ->has('paystackPublicKey')
            ->has('ghanaRegions')
            ->has('ghanaCitiesByRegion')
            ->has('shippingRatesByRegion'));
    }

    public function test_checkout_creates_order_and_redirects_to_paystack(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-session',
                    'access_code' => 'ACCESS123',
                    'reference' => 'LH-TESTREF',
                ],
            ], 200),
        ]);

        $product = $this->createShopProduct(stock: 5, priceCents: 2500);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($product), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '',
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', 'https://checkout.paystack.com/test-session');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'buyer@example.com',
            'payment_status' => PaymentStatus::Pending->value,
            'status' => OrderStatus::PendingPayment->value,
            'subtotal_cents' => 2500,
            'shipping_cents' => 6500,
            'total_cents' => 9000,
            'shipping_city' => 'Tema',
            'shipping_region' => 'Greater Accra',
        ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $product->user_id,
            'quantity' => 1,
            'line_total_cents' => 2500,
        ]);
    }

    public function test_checkout_uses_city_shipping_override_when_configured(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-session',
                    'access_code' => 'ACCESS123',
                    'reference' => 'LH-TESTREF',
                ],
            ], 200),
        ]);

        $product = $this->createShopProduct(stock: 5, priceCents: 2500);

        $payload = $this->validCheckoutPayload($product);
        $payload['shipping_region'] = 'Greater Accra';
        $payload['shipping_city'] = 'Afienya';

        $response = $this->post(route('checkout.store'), $payload, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('orders', [
            'shipping_city' => 'Afienya',
            'shipping_cents' => 6500,
            'total_cents' => 9000,
        ]);
    }

    public function test_checkout_rejects_city_not_in_selected_region(): void
    {
        $product = $this->createShopProduct(stock: 5, priceCents: 2500);

        $payload = $this->validCheckoutPayload($product);
        $payload['shipping_region'] = 'Greater Accra';
        $payload['shipping_city'] = 'Kumasi';

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertSessionHasErrors('shipping_city');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_mixed_vendor_cart(): void
    {
        $vendorA = $this->vendorWithApplication('Shop A', 'shop-a');
        $vendorB = $this->vendorWithApplication('Shop B', 'shop-b');
        $productA = $this->createShopProduct(stock: 5, priceCents: 2500, vendor: $vendorA);
        $productB = $this->createShopProduct(stock: 5, priceCents: 1800, vendor: $vendorB, sku: 'LH-SHOP-002');

        $payload = $this->validCheckoutPayload($productA);
        $payload['items'][] = [
            'product_id' => $productB->id,
            'quantity' => 1,
            'attributes' => null,
        ];

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $product = $this->createShopProduct(stock: 0, priceCents: 2500);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($product));

        $response->assertSessionHasErrors('items.0');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_callback_marks_order_paid_and_reduces_stock(): void
    {
        Bus::fake();

        $product = $this->createShopProduct(stock: 4, priceCents: 3000);

        $order = Order::create([
            'order_number' => 'LH-TEST-001',
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'paystack_reference' => 'LH-PAYREF123',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 3000,
            'shipping_cents' => 0,
            'total_cents' => 3000,
            'currency' => 'GHS',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $product->user_id,
            'product_title' => $product->title,
            'product_brand' => $product->brand,
            'unit_price_cents' => 3000,
            'quantity' => 1,
            'line_total_cents' => 3000,
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 3000,
                    'id' => '987654',
                    'reference' => 'LH-PAYREF123',
                ],
            ], 200),
        ]);

        $response = $this->get(route('checkout.callback', ['reference' => 'LH-PAYREF123']));

        $response->assertRedirect(route('checkout.success', $order));

        $order->refresh();
        $product->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(3, $product->stock_quantity);
        Bus::assertDispatched(SendOrderPaidSms::class, fn (SendOrderPaidSms $job) => $job->orderId === $order->id);
        Bus::assertDispatched(
            SendVendorNewOrderSms::class,
            fn (SendVendorNewOrderSms $job) => $job->orderId === $order->id && $job->vendorUserId === $product->user_id,
        );
    }

    public function test_vendor_can_view_paid_orders(): void
    {
        $vendor = $this->vendorWithApplication();
        $product = $this->createShopProduct(stock: 2, priceCents: 1500, vendor: $vendor);

        $order = Order::create([
            'order_number' => 'LH-TEST-002',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-PAYREF456',
            'customer_name' => 'Kofi Mensah',
            'customer_email' => 'kofi@example.com',
            'customer_phone' => '0201112233',
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

        $response = $this->actingAs($vendor)->get(route('vendor.orders.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Vendor/Orders/Index')
            ->has('orders', 1)
            ->where('orders.0.order_number', 'LH-TEST-002')
            ->missing('orders.0.customer_phone'));
    }

    public function test_checkout_applies_promo_code(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-session',
                    'access_code' => 'ACCESS123',
                    'reference' => 'LH-TESTREF',
                ],
            ], 200),
        ]);

        $product = $this->createShopProduct(stock: 5, priceCents: 2500);

        $response = $this->post(route('checkout.store'), [
            ...$this->validCheckoutPayload($product),
            'promo_code' => 'WELCOME10',
        ], [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'buyer@example.com',
            'subtotal_cents' => 2500,
            'discount_cents' => 250,
            'shipping_cents' => 6500,
            'total_cents' => 8750,
            'promo_code' => 'WELCOME10',
            'promo_cost_bearer' => 'mummish',
        ]);
    }

    public function test_checkout_promo_validation_endpoint(): void
    {
        $product = $this->createShopProduct(stock: 5, priceCents: 2500);

        $response = $this->postJson(route('checkout.promo.validate'), [
            'promo_code' => 'WELCOME10',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'attributes' => null,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'promo_code' => 'WELCOME10',
            'discount_cents' => 250,
            'subtotal_cents' => 2500,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validCheckoutPayload(Product $product): array
    {
        return [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'attributes' => null,
                ],
            ],
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_address_line2' => null,
            'shipping_city' => 'Tema',
            'shipping_region' => 'Greater Accra',
            'shipping_notes' => null,
        ];
    }

    private function createShopProduct(int $stock, int $priceCents, ?User $vendor = null, string $sku = 'LH-SHOP-001'): Product
    {
        $vendor ??= $this->vendorWithApplication();

        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Shop Product',
            'sku' => $sku,
            'category' => 'toys_development',
            'brand' => 'Fisher-Price',
            'price_cents' => $priceCents,
            'stock_quantity' => $stock,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => ['https://example.com/product.jpg'],
            'allows_customization' => false,
        ]);
    }

    private function vendorWithApplication(string $shopName = 'Test Shop', string $shopSlug = 'test-shop'): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Vendor,
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Vendor',
            'shop_name' => $shopName,
            'shop_slug' => $shopSlug,
            'business_email' => $user->email,
            'phone' => '0240000000',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }
}
