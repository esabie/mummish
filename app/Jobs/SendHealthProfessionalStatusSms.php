<?php

namespace App\Jobs;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Models\HealthProfessional;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendHealthProfessionalStatusSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public HealthProfessional $professional,
        public HealthProfessionalApprovalStatus $status,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthProfessionalStatusSms: job started.', [
            'attempt' => $this->attempts(),
            'health_professional_id' => $this->professional->id,
            'status' => $this->status->value,
        ]);

        $phone = $this->professional->phone;
        if ($phone === null || trim($phone) === '') {
            Log::warning('SendHealthProfessionalStatusSms: no phone on professional.', [
                'health_professional_id' => $this->professional->id,
            ]);

            return;
        }

        $firstName = $this->firstName();
        $appName = config('app.name', 'Mummish');

        $message = match ($this->status) {
            HealthProfessionalApprovalStatus::Approved => "Hi {$firstName}, your {$appName} Health Services profile has been approved and is now live. Patients can book with you.",
            HealthProfessionalApprovalStatus::Rejected => $this->reasonMessage(
                "Hi {$firstName}, your {$appName} Health Services profile was not approved.",
            ),
            HealthProfessionalApprovalStatus::Suspended => $this->reasonMessage(
                "Hi {$firstName}, your {$appName} Health Services profile has been suspended and is no longer visible.",
            ),
            default => null,
        };

        if ($message === null) {
            Log::info('SendHealthProfessionalStatusSms: no SMS sent for unsupported status.', [
                'health_professional_id' => $this->professional->id,
                'status' => $this->status->value,
            ]);

            return;
        }

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthProfessionalStatusSms: failed to send status SMS.', [
                'attempt' => $this->attempts(),
                'health_professional_id' => $this->professional->id,
                'status' => $this->status->value,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthProfessionalStatusSms: status SMS sent.', [
            'attempt' => $this->attempts(),
            'health_professional_id' => $this->professional->id,
            'status' => $this->status->value,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }

    private function firstName(): string
    {
        $name = trim((string) $this->professional->name);

        if ($name === '') {
            return 'there';
        }

        return explode(' ', $name)[0];
    }

    private function reasonMessage(string $prefix): string
    {
        $reason = trim((string) $this->professional->rejection_reason);
        $reason = $reason !== '' ? Str::limit($reason, 120) : 'Please contact support for more information.';

        return "{$prefix} Reason: {$reason}";
    }
}
