<?php

namespace App\Jobs;

use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAccountWelcomeSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly string $firstName,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendAccountWelcomeSms: job started.', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            'first_name_length' => strlen(trim($this->firstName)),
        ]);

        $appName = config('app.name', 'Mummish');
        $name = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        $message = "Hi {$name}, welcome to {$appName}! Your seller account has been successfully created. Log in to start listing and selling your products today!";

        $sent = $mnotifySms->send($this->phone, $message);

        if ($sent) {
            Log::info('SendAccountWelcomeSms: Mnotify reported success.', [
                'attempt' => $this->attempts(),
                'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            ]);

            return;
        }

        Log::warning('SendAccountWelcomeSms: SMS was not sent (skipped, API error, or non-success response).', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
        ]);
    }
}
