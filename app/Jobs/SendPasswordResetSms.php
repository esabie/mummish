<?php

namespace App\Jobs;

use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPasswordResetSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly string $firstName,
        public readonly string $resetUrl,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendPasswordResetSms: job started.', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            'first_name_length' => strlen(trim($this->firstName)),
            'reset_url_host' => parse_url($this->resetUrl, PHP_URL_HOST) ?: '(unknown)',
        ]);

        $appName = config('app.name', 'Mummish');
        $name = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        $message = "Hi {$name}, reset your {$appName} password using this link: {$this->resetUrl}";

        $sent = $mnotifySms->send($this->phone, $message);

        if ($sent) {
            Log::info('SendPasswordResetSms: Mnotify reported success.', [
                'attempt' => $this->attempts(),
                'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            ]);

            return;
        }

        Log::warning('SendPasswordResetSms: SMS was not sent (skipped, API error, or non-success response).', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
        ]);
    }
}
