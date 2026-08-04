<?php

namespace Tests\Unit;

use App\Jobs\SendCustomerWelcomeSms;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendCustomerWelcomeSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_customer_welcome_sms(): void
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

        (new SendCustomerWelcomeSms('0241234567', 'Ama'))
            ->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['message'] ?? '';
            $recipients = $body['recipient'] ?? [];

            return is_array($recipients)
                && collect($recipients)->contains(fn ($phone) => str_contains((string) $phone, '241234567'))
                && str_contains($message, 'Welcome aboard, Ama!')
                && str_contains($message, 'save your favourites')
                && str_contains($message, 'faster checkout')
                && ! str_contains($message, 'seller');
        });
    }
}
