<?php

namespace Tests\Feature\Vendor;

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
use App\Notifications\VendorApplicationReviewedNotification;
use App\Notifications\VendorFulfillmentStatusNotification;
use App\Notifications\VendorNewOrderNotification;
use App\Services\CheckoutService;
use App\Services\VendorApplicationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VendorNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_notifies_vendor(): void
    {
        Notification::fake();

        $vendor = $this->vendorWithApplication();
        $product = $this->createProduct($vendor, 5000);
        $order = $this->createPendingOrder($vendor, $product, 5000);

        app(CheckoutService::class)->markOrderPaidFromPaystack($order, [
            'status' => 'success',
            'amount' => 5000,
            'id' => 'txn_1',
            'reference' => $order->paystack_reference,
        ]);

        Notification::assertSentTo($vendor, VendorNewOrderNotification::class);
    }

    public function test_application_approval_notifies_vendor(): void
    {
        Notification::fake();

        $vendor = $this->vendorWithApplication();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $application = $vendor->vendorApplication;

        app(VendorApplicationReviewService::class)->approve($application, $admin);

        Notification::assertSentTo($vendor, VendorApplicationReviewedNotification::class);
    }

    public function test_fulfillment_status_change_notifies_vendor(): void
    {
        Notification::fake();

        $vendor = $this->vendorWithApplication();
        $product = $this->createProduct($vendor, 5000);
        $order = $this->createPaidOrder($vendor, $product, 5000);

        $fulfillment = VendorOrderFulfillment::create([
            'order_id' => $order->id,
            'vendor_user_id' => $vendor->id,
            'status' => VendorFulfillmentStatus::ReadyForPickup,
            'ready_for_pickup_at' => now(),
            'fulfilled_at' => now(),
        ]);

        Notification::assertNothingSent();

        $fulfillment->update([
            'status' => VendorFulfillmentStatus::PickedUp,
            'picked_up_at' => now(),
        ]);

        Notification::assertSentTo($vendor, VendorFulfillmentStatusNotification::class);
    }

    public function test_vendor_can_list_and_mark_notifications_read(): void
    {
        $vendor = $this->vendorWithApplication();
        $vendor->notify(new VendorApplicationReviewedNotification($vendor->vendorApplication));

        $this->actingAs($vendor)
            ->getJson(route('vendor.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');

        $notificationId = $vendor->notifications()->first()->id;

        $this->actingAs($vendor)
            ->postJson(route('vendor.notifications.read', $notificationId))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $vendor->notify(new VendorApplicationReviewedNotification($vendor->vendorApplication->fresh()));
        $vendor->notify(new VendorApplicationReviewedNotification($vendor->vendorApplication->fresh()));

        $this->actingAs($vendor)
            ->postJson(route('vendor.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_vendor_dashboard_shares_unread_notification_count(): void
    {
        $vendor = $this->vendorWithApplication();
        $vendor->notify(new VendorApplicationReviewedNotification($vendor->vendorApplication));

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('vendorNotifications.unread_count', 1)
            );
    }

    private function vendorWithApplication(): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        return $user->fresh(['vendorApplication']);
    }

    private function createProduct(User $vendor, int $priceCents): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Fairly New Stroller',
            'sku' => 'STR-01',
            'category' => 'toys_development',
            'price_cents' => $priceCents,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
            'description' => 'A gently used stroller.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/a.jpg',
                'https://example.com/b.jpg',
                'https://example.com/c.jpg',
            ],
        ]);
    }

    private function createPendingOrder(User $vendor, Product $product, int $lineTotalCents): Order
    {
        $order = Order::create([
            'order_number' => 'LH-NOTIF-001',
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'paystack_reference' => 'LH-REF-NOTIF-001',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => $lineTotalCents,
            'shipping_cents' => 0,
            'total_cents' => $lineTotalCents,
            'currency' => 'GHS',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'unit_price_cents' => $lineTotalCents,
            'quantity' => 1,
            'line_total_cents' => $lineTotalCents,
        ]);

        return $order->fresh(['items']);
    }

    private function createPaidOrder(User $vendor, Product $product, int $lineTotalCents): Order
    {
        $order = $this->createPendingOrder($vendor, $product, $lineTotalCents);
        $order->update([
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        return $order->fresh(['items']);
    }
}
