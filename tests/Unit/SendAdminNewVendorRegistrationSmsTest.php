<?php

namespace Tests\Unit;

use App\Enums\VendorApplicationStatus;
use App\Jobs\SendAdminNewVendorRegistrationSms;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendAdminNewVendorRegistrationSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_admin_sms_with_pending_count(): void
    {
        config([
            'marketplace.admin_notification_phones' => ['0208062428'],
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $user = User::factory()->create();
        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => '0208062428',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $application = VendorApplication::first();

        (new SendAdminNewVendorRegistrationSms($application))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Little Knot')
                && str_contains($message, 'Ama')
                && str_contains($message, 'Pending applications: 1');
        });
    }
}
