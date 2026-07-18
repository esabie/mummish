<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVendorNewOrderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $vendorUserId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendVendorNewOrderSms: job started.', [
            'attempt' => $this->attempts(),
            'order_id' => $this->orderId,
            'vendor_user_id' => $this->vendorUserId,
        ]);

        $order = Order::query()->find($this->orderId);

        if (! $order) {
            Log::warning('SendVendorNewOrderSms: order not found.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $vendor = User::query()->with('vendorApplication')->find($this->vendorUserId);

        if (! $vendor) {
            Log::warning('SendVendorNewOrderSms: vendor not found.', [
                'vendor_user_id' => $this->vendorUserId,
                'order_id' => $order->id,
            ]);

            return;
        }

        $phone = trim((string) ($vendor->vendorApplication?->phone ?? ''));

        if ($phone === '') {
            Log::warning('SendVendorNewOrderSms: vendor phone missing.', [
                'vendor_user_id' => $vendor->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        $firstName = trim((string) ($vendor->vendorApplication?->first_name ?? ''));
        if ($firstName === '') {
            $firstName = trim(strtok((string) $vendor->name, ' ') ?: 'there');
        }

        $appName = config('app.name', 'Mummish');
        $message = "Hi {$firstName}, you have a new {$appName} order ({$order->order_number}). Log in to Mummish Vendor Central to fulfill it.";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendVendorNewOrderSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'vendor_user_id' => $vendor->id,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendVendorNewOrderSms: vendor new-order SMS sent.', [
            'attempt' => $this->attempts(),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'vendor_user_id' => $vendor->id,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
