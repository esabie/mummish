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

class SendOrderPaidSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendOrderPaidSms: job started.', [
            'attempt' => $this->attempts(),
            'order_id' => $this->orderId,
        ]);

        $order = Order::query()->find($this->orderId);

        if (! $order) {
            Log::warning('SendOrderPaidSms: order not found.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $phone = trim((string) $order->customer_phone);

        if ($phone === '') {
            Log::warning('SendOrderPaidSms: customer phone missing on order.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $order->customer_name, ' ') ?: 'Customer');
        $appName = config('app.name', 'Mummish');
        $formattedTotal = 'GHS '.number_format(((int) $order->total_cents) / 100, 2);

        $message = "Hi {$firstName}, payment received for order {$order->order_number} ({$formattedTotal}). {$appName} will share delivery updates.";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendOrderPaidSms: failed to send order confirmation SMS.', [
                'attempt' => $this->attempts(),
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendOrderPaidSms: customer payment SMS sent.', [
            'attempt' => $this->attempts(),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
