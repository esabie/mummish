<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorNewOrderSms;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendVendorNewOrderSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_vendor_sms_to_fulfill_new_order(): void
    {
        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
            'app.name' => 'Mummish',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $vendor = User::factory()->create(['role' => UserRole::Vendor, 'name' => 'Ama Mensah']);

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

        $order = Order::create([
            'order_number' => 'LH-SMS-001',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'LH-REF-SMS-001',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '0201112233',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 5000,
            'shipping_cents' => 0,
            'total_cents' => 5000,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        (new SendVendorNewOrderSms($order->id, $vendor->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['message'] ?? '';
            $recipients = $body['recipient'] ?? [];

            return is_array($recipients)
                && collect($recipients)->contains(fn ($phone) => str_contains((string) $phone, '241234567'))
                && str_contains($message, 'Hi Ama')
                && str_contains($message, 'new Mummish order')
                && str_contains($message, 'LH-SMS-001')
                && str_contains($message, 'Vendor Central')
                && str_contains($message, 'fulfill');
        });
    }
}
