<?php

namespace App\Jobs;

use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomerAccountSetupSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly string $firstName,
        public readonly string $setupUrl,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendCustomerAccountSetupSms: job started.', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            'setup_url_host' => parse_url($this->setupUrl, PHP_URL_HOST) ?: '(unknown)',
        ]);

        $appName = config('app.name', 'Mummish');
        $name = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        $message = "Hi {$name}, welcome to {$appName}! We created an account for your order. Set your password here: {$this->setupUrl}";

        $sent = $mnotifySms->send($this->phone, $message);

        if ($sent) {
            Log::info('SendCustomerAccountSetupSms: Mnotify reported success.', [
                'attempt' => $this->attempts(),
                'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            ]);

            return;
        }

        Log::warning('SendCustomerAccountSetupSms: SMS was not sent (skipped, API error, or non-success response).', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
        ]);
    }
}
