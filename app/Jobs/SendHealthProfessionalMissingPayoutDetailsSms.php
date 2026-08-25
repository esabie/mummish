<?php

namespace App\Jobs;

use App\Models\HealthProfessional;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthProfessionalMissingPayoutDetailsSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $healthProfessionalId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthProfessionalMissingPayoutDetailsSms: job started.', [
            'attempt' => $this->attempts(),
            'health_professional_id' => $this->healthProfessionalId,
        ]);

        $professional = HealthProfessional::query()->find($this->healthProfessionalId);

        if ($professional === null) {
            Log::warning('SendHealthProfessionalMissingPayoutDetailsSms: professional not found.', [
                'health_professional_id' => $this->healthProfessionalId,
            ]);

            return;
        }

        if ($professional->hasPaymentDetails()) {
            Log::info('SendHealthProfessionalMissingPayoutDetailsSms: skipped — payment details already saved.', [
                'health_professional_id' => $professional->id,
            ]);

            return;
        }

        $phone = trim((string) $professional->phone);
        if ($phone === '') {
            Log::warning('SendHealthProfessionalMissingPayoutDetailsSms: phone missing.', [
                'health_professional_id' => $professional->id,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $professional->name, ' ') ?: 'there');
        $appName = config('app.name', 'Mummish');

        $message = "Hi {$firstName}, please add payout details for your {$appName} Health Services profile so we can pay you after completed consultations. Go to Dashboard → Payment details. Thank you for partnering with us!";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthProfessionalMissingPayoutDetailsSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'health_professional_id' => $professional->id,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthProfessionalMissingPayoutDetailsSms: SMS sent.', [
            'attempt' => $this->attempts(),
            'health_professional_id' => $professional->id,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
