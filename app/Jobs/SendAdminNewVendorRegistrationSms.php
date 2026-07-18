<?php

namespace App\Jobs;

use App\Enums\VendorApplicationStatus;
use App\Models\VendorApplication;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAdminNewVendorRegistrationSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public VendorApplication $application,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        $phones = config('marketplace.admin_notification_phones', []);

        if ($phones === []) {
            Log::warning('SendAdminNewVendorRegistrationSms: no admin notification phones configured.');

            return;
        }

        $this->application->loadMissing('user');

        $pendingCount = VendorApplication::query()
            ->where('status', VendorApplicationStatus::Pending)
            ->count();

        $shopName = trim($this->application->shop_name);
        $firstName = trim($this->application->first_name);
        $appName = config('app.name', 'Mummish');

        $message = "New vendor signup: {$shopName} ({$firstName}). Pending applications: {$pendingCount}.";

        if (filled($this->application->referral_code)) {
            $message .= " Referral: {$this->application->referral_code}.";
        }

        Log::info('SendAdminNewVendorRegistrationSms: sending.', [
            'application_id' => $this->application->id,
            'pending_count' => $pendingCount,
            'admin_phone_count' => count($phones),
        ]);

        foreach ($phones as $phone) {
            $sent = $mnotifySms->send($phone, $message);

            if (! $sent) {
                Log::warning('SendAdminNewVendorRegistrationSms: failed for admin phone.', [
                    'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
                    'application_id' => $this->application->id,
                ]);
            }
        }
    }
}
