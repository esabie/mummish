<?php

namespace App\Jobs;

use App\Models\VendorApplication;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVendorMissingPayoutDetailsSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $vendorApplicationId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendVendorMissingPayoutDetailsSms: job started.', [
            'attempt' => $this->attempts(),
            'vendor_application_id' => $this->vendorApplicationId,
        ]);

        $application = VendorApplication::query()->find($this->vendorApplicationId);

        if ($application === null) {
            Log::warning('SendVendorMissingPayoutDetailsSms: application not found.', [
                'vendor_application_id' => $this->vendorApplicationId,
            ]);

            return;
        }

        if ($application->hasPaymentDetails()) {
            Log::info('SendVendorMissingPayoutDetailsSms: skipped — payment details already saved.', [
                'vendor_application_id' => $application->id,
            ]);

            return;
        }

        $phone = trim((string) $application->phone);
        if ($phone === '') {
            Log::warning('SendVendorMissingPayoutDetailsSms: phone missing.', [
                'vendor_application_id' => $application->id,
            ]);

            return;
        }

        $firstName = trim((string) $application->first_name);
        $firstName = $firstName !== '' ? $firstName : 'there';
        $shopName = trim((string) $application->shop_name);
        $shopName = $shopName !== '' ? $shopName : 'your shop';

        $message = "Hi {$firstName}, please add payout details for {$shopName} on The Mummish Vendor Central so we can pay you after sales. Go to Dashboard → Payment details. Thank you for partnering with us!";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendVendorMissingPayoutDetailsSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'vendor_application_id' => $application->id,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendVendorMissingPayoutDetailsSms: SMS sent.', [
            'attempt' => $this->attempts(),
            'vendor_application_id' => $application->id,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
