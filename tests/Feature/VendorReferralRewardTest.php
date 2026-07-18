<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorReferralRewardStatus;
use App\Enums\VendorReferralRewardType;
use App\Jobs\SendOrderPaidSms;
use App\Jobs\SendVendorReferralRewardPaidSms;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorReferralReward;
use App\Models\VendorReferrer;
use App\Services\VendorApplicationReviewService;
use App\Services\VendorReferralRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorReferralRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_signup_links_active_referrer(): void
    {
        $referrer = $this->createReferrer('PARTNER2026');

        $this->post(route('vendor.signup.store'), $this->validPayload([
            'referral_code' => 'partner2026',
        ]))->assertRedirect(route('vendor.inventory.index'));

        $application = VendorApplication::first();
        $this->assertSame($referrer->id, $application->vendor_referrer_id);
        $this->assertSame('PARTNER2026', $application->referral_code);
    }

    public function test_approving_referred_vendor_creates_registration_reward(): void
    {
        $referrer = $this->createReferrer('PARTNER2026', registrationRewardCents: 7500);
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $application = VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-123456789-0',
            'category' => 'toys_development',
            'referral_code' => $referrer->code,
            'vendor_referrer_id' => $referrer->id,
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        app(VendorApplicationReviewService::class)->approve($application, $admin);

        $reward = VendorReferralReward::first();
        $this->assertNotNull($reward);
        $this->assertSame(VendorReferralRewardType::Registration, $reward->type);
        $this->assertSame(7500, $reward->amount_cents);
        $this->assertSame(VendorReferralRewardStatus::Pending, $reward->status);
        $this->assertSame($referrer->id, $reward->vendor_referrer_id);
    }

    public function test_paid_order_from_referred_vendor_creates_transaction_reward(): void
    {
        Bus::fake();

        $referrer = $this->createReferrer('PARTNER2026', transactionCommissionBps: 500);
        $vendor = $this->vendorWithReferrer($referrer);
        $product = $this->createShopProduct($vendor, priceCents: 10000);

        $order = Order::create([
            'order_number' => 'LH-REF-001',
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'paystack_reference' => 'LH-REF-PAY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 10000,
            'shipping_cents' => 0,
            'total_cents' => 10000,
            'currency' => 'GHS',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'unit_price_cents' => 10000,
            'quantity' => 1,
            'line_total_cents' => 10000,
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 10000,
                    'id' => '12345',
                    'reference' => 'LH-REF-PAY',
                ],
            ], 200),
        ]);

        $this->get(route('checkout.callback', ['reference' => 'LH-REF-PAY']));

        $reward = VendorReferralReward::query()
            ->where('order_item_id', $item->id)
            ->first();

        $this->assertNotNull($reward);
        $this->assertSame(VendorReferralRewardType::Transaction, $reward->type);
        $this->assertSame(500, $reward->amount_cents);
        $this->assertSame($referrer->id, $reward->vendor_referrer_id);
    }

    public function test_transaction_reward_is_not_duplicated_for_same_order_item(): void
    {
        $referrer = $this->createReferrer('PARTNER2026', transactionCommissionBps: 500);
        $vendor = $this->vendorWithReferrer($referrer);
        $product = $this->createShopProduct($vendor, priceCents: 10000);

        $order = Order::create([
            'order_number' => 'LH-REF-002',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-REF-PAY-2',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 10000,
            'shipping_cents' => 0,
            'total_cents' => 10000,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'product_title' => $product->title,
            'unit_price_cents' => 10000,
            'quantity' => 1,
            'line_total_cents' => 10000,
        ]);

        $service = app(VendorReferralRewardService::class);
        $service->awardTransactionRewards($order->fresh(['items']));
        $service->awardTransactionRewards($order->fresh(['items']));

        $this->assertSame(1, VendorReferralReward::query()->where('order_item_id', $item->id)->count());
    }

    public function test_admin_can_mark_referral_reward_paid(): void
    {
        Bus::fake();

        $referrer = $this->createReferrer('PARTNER2026');
        $reward = VendorReferralReward::create([
            'vendor_referrer_id' => $referrer->id,
            'type' => VendorReferralRewardType::Registration,
            'amount_cents' => 5000,
            'description' => 'Test reward',
            'status' => VendorReferralRewardStatus::Pending,
        ]);

        app(VendorReferralRewardService::class)->markPaid($reward);

        $reward->refresh();
        $this->assertSame(VendorReferralRewardStatus::Paid, $reward->status);
        $this->assertNotNull($reward->paid_at);
        Bus::assertDispatched(SendVendorReferralRewardPaidSms::class, fn (SendVendorReferralRewardPaidSms $job) => $job->rewardId === $reward->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'email' => 'ama@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'shop_name' => 'Little Knot',
            'phone' => '0241234567',
            'ghana_card_id' => 'GHA-123456789-0',
            'category' => 'toys_development',
            'terms_accepted' => true,
        ], $overrides);
    }

    private function createReferrer(
        string $code,
        ?int $registrationRewardCents = 5000,
        ?int $transactionCommissionBps = 200,
    ): VendorReferrer {
        return VendorReferrer::create([
            'code' => strtoupper($code),
            'name' => 'Partner User',
            'phone' => '0241234567',
            'registration_reward_cents' => $registrationRewardCents,
            'transaction_commission_bps' => $transactionCommissionBps,
            'is_active' => true,
        ]);
    }

    private function vendorWithReferrer(VendorReferrer $referrer): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Vendor',
            'shop_name' => 'Referred Shop',
            'shop_slug' => 'referred-shop',
            'business_email' => $user->email,
            'phone' => '0240000000',
            'ghana_card_id' => 'GHA-987654321-1',
            'category' => 'toys_development',
            'referral_code' => $referrer->code,
            'vendor_referrer_id' => $referrer->id,
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }

    private function createShopProduct(User $vendor, int $priceCents): Product
    {
        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Shop Product',
            'sku' => 'LH-REF-PROD',
            'category' => 'toys_development',
            'price_cents' => $priceCents,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => ['https://example.com/product.jpg'],
        ]);
    }
}
