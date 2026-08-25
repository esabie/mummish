<?php

namespace App\Jobs;

use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomerWelcomeSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly string $firstName,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendCustomerWelcomeSms: job started.', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            'first_name_length' => strlen(trim($this->firstName)),
        ]);

        $name = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        $message = "Welcome aboard, {$name}! Shop with ease, save your favourites, track your orders, and enjoy faster checkout.";

        $sent = $mnotifySms->send($this->phone, $message);

        if ($sent) {
            Log::info('SendCustomerWelcomeSms: Mnotify reported success.', [
                'attempt' => $this->attempts(),
                'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
            ]);

            return;
        }

        Log::warning('SendCustomerWelcomeSms: SMS was not sent (skipped, API error, or non-success response).', [
            'attempt' => $this->attempts(),
            'phone_masked' => MnotifySmsService::maskPhoneForLog($this->phone),
        ]);
    }
}
