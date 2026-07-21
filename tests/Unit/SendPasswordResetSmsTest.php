<?php

namespace Tests\Unit;

use App\Jobs\SendPasswordResetSms;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendPasswordResetSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_password_reset_sms_with_reset_link(): void
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

        $resetUrl = 'https://example.test/reset-password/abc123?email=seller%40example.com';

        (new SendPasswordResetSms('0241234567', 'Ama', $resetUrl))
            ->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($resetUrl) {
            $body = $request->data();
            $message = $body['message'] ?? '';
            $recipients = $body['recipient'] ?? [];

            return is_array($recipients)
                && collect($recipients)->contains(fn ($phone) => str_contains((string) $phone, '241234567'))
                && str_contains($message, 'Hi Ama')
                && str_contains($message, 'reset your Mummish password')
                && str_contains($message, $resetUrl);
        });
    }
}
