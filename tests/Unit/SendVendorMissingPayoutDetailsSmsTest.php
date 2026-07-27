<?php

namespace Tests\Unit;

use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorMissingPayoutDetailsSms;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendVendorMissingPayoutDetailsSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reminder_sms_with_confirmed_copy(): void
    {
        config([
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
        $application = VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        (new SendVendorMissingPayoutDetailsSms($application->id))
            ->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $message = $request->data()['message'] ?? '';

            return $message === 'Hi Ama, please add payout details for Little Knot on The Mummish Vendor Central so we can pay you after sales. Go to Dashboard → Payment details. Thank you for partnering with us!';
        });
    }

    public function test_skips_when_payment_details_already_saved(): void
    {
        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake();

        $user = User::factory()->create();
        $application = VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
            'payment_method' => 'bank',
            'bank_name' => 'GCB BANK',
            'bank_account_name' => 'Ama Mensah',
            'bank_account_number' => '0123456789',
        ]);

        (new SendVendorMissingPayoutDetailsSms($application->id))
            ->handle(app(MnotifySmsService::class));

        Http::assertNothingSent();
    }
}
