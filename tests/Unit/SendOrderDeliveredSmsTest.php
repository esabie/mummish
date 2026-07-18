<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendOrderDeliveredSms;
use App\Models\Order;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendOrderDeliveredSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_thank_you_sms_on_delivery_confirmation(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $order = Order::create([
            'order_number' => 'LH-DELIVERED-001',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'REF-DELIVERED-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'jd@yahoo.com',
            'customer_phone' => '0240000000',
            'shipping_address_line1' => '12 Market Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 7500,
            'shipping_cents' => 1500,
            'total_cents' => 9000,
            'paid_at' => now(),
        ]);

        (new SendOrderDeliveredSms($order->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Hi John')
                && str_contains($message, 'LH-DELIVERED-001')
                && str_contains($message, 'thank you for shopping with Mummish');
        });
    }
}
