<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\VendorOrderFulfillment;
use App\Services\MnotifySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAdminVendorOrderReadyForPickupSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $vendorUserId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendAdminVendorOrderReadyForPickupSms: job started.', [
            'attempt' => $this->attempts(),
            'order_id' => $this->orderId,
            'vendor_user_id' => $this->vendorUserId,
        ]);

        $phones = config('marketplace.admin_notification_phones', []);

        if ($phones === []) {
            Log::warning('SendAdminVendorOrderReadyForPickupSms: no admin notification phones configured.');

            return;
        }

        $order = Order::query()->find($this->orderId);
        $fulfillment = VendorOrderFulfillment::query()
            ->with('vendor.vendorApplication')
            ->where('order_id', $this->orderId)
            ->where('vendor_user_id', $this->vendorUserId)
            ->first();

        if (! $order || ! $fulfillment) {
            Log::warning('SendAdminVendorOrderReadyForPickupSms: order or fulfillment not found.', [
                'order_id' => $this->orderId,
                'vendor_user_id' => $this->vendorUserId,
            ]);

            return;
        }

        $shopName = $fulfillment->vendor?->vendorApplication?->shop_name
            ?? $fulfillment->vendor?->email
            ?? 'Vendor';

        $message = "Pickup ready: {$shopName} has packed order {$order->order_number}.";

        foreach ($phones as $phone) {
            Log::info('SendAdminVendorOrderReadyForPickupSms: attempting admin SMS.', [
                'attempt' => $this->attempts(),
                'order_id' => $this->orderId,
                'vendor_user_id' => $this->vendorUserId,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            $sent = $mnotifySms->send($phone, $message);

            if (! $sent) {
                Log::warning('SendAdminVendorOrderReadyForPickupSms: failed for admin phone.', [
                    'attempt' => $this->attempts(),
                    'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
                    'order_id' => $this->orderId,
                    'vendor_user_id' => $this->vendorUserId,
                ]);

                continue;
            }

            Log::info('SendAdminVendorOrderReadyForPickupSms: admin SMS sent.', [
                'attempt' => $this->attempts(),
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
                'order_id' => $this->orderId,
                'vendor_user_id' => $this->vendorUserId,
            ]);
        }
    }
}
