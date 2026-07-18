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
use Illuminate\Support\Str;

class SendVendorApplicationStatusSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public VendorApplication $application,
        public VendorApplicationStatus $status,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendVendorApplicationStatusSms: job started.', [
            'attempt' => $this->attempts(),
            'application_id' => $this->application->id,
            'status' => $this->status->value,
        ]);

        $phone = $this->application->phone;
        if ($phone === null || trim($phone) === '') {
            Log::warning('SendVendorApplicationStatusSms: no phone on application.', [
                'application_id' => $this->application->id,
            ]);

            return;
        }

        $firstName = trim($this->application->first_name) !== '' ? trim($this->application->first_name) : 'there';
        $shopName = trim($this->application->shop_name);
        $appName = config('app.name', 'Mummish');

        $message = match ($this->status) {
            VendorApplicationStatus::Approved => "Hi {$firstName}, great news! Your seller application for {$shopName} has been approved. Log in to list unlimited products and start selling.",
            VendorApplicationStatus::Rejected => $this->rejectionMessage($firstName, $appName, $shopName),
            default => null,
        };

        if ($message === null) {
            Log::info('SendVendorApplicationStatusSms: no SMS sent for unsupported status.', [
                'application_id' => $this->application->id,
                'status' => $this->status->value,
            ]);

            return;
        }

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendVendorApplicationStatusSms: failed to send status SMS.', [
                'attempt' => $this->attempts(),
                'application_id' => $this->application->id,
                'status' => $this->status->value,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendVendorApplicationStatusSms: status SMS sent.', [
            'attempt' => $this->attempts(),
            'application_id' => $this->application->id,
            'status' => $this->status->value,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }

    private function rejectionMessage(string $firstName, string $appName, string $shopName): string
    {
        $reason = trim((string) $this->application->rejection_reason);
        $reason = $reason !== '' ? Str::limit($reason, 120) : 'Please contact support for more information.';

        return "Hi {$firstName}, your {$appName} seller application for {$shopName} was not approved. Reason: {$reason}";
    }
}
