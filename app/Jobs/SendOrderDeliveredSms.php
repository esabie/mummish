<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderDeliveredSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendOrderDeliveredSms: job started.', [
            'attempt' => $this->attempts(),
            'order_id' => $this->orderId,
        ]);

        $order = Order::query()->find($this->orderId);

        if (! $order) {
            Log::warning('SendOrderDeliveredSms: order not found.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $phone = trim((string) $order->customer_phone);

        if ($phone === '') {
            Log::warning('SendOrderDeliveredSms: customer phone missing on order.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $order->customer_name, ' ') ?: 'Customer');
        $appName = config('app.name', 'Mummish');

        $message = "Hi {$firstName}, thanks for confirming delivery of order {$order->order_number}. We hope you love it — thank you for shopping with {$appName}!";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendOrderDeliveredSms: failed to send delivery thank-you SMS.', [
                'attempt' => $this->attempts(),
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendOrderDeliveredSms: customer delivery thank-you SMS sent.', [
            'attempt' => $this->attempts(),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
