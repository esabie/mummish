<?php

namespace App\Notifications;

use App\Enums\VendorFulfillmentStatus;
use App\Models\VendorOrderFulfillment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorFulfillmentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorOrderFulfillment $fulfillment,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $order = $this->fulfillment->order;
        $orderNumber = $order?->order_number ?? 'your order';
        $status = $this->fulfillment->status;

        [$title, $body] = match ($status) {
            VendorFulfillmentStatus::PickedUp => [
                'Order picked up',
                "Order {$orderNumber} was picked up from your shop.",
            ],
            VendorFulfillmentStatus::ReceivedAtWarehouse => [
                'Order at warehouse',
                "Order {$orderNumber} arrived at the Mummish warehouse.",
            ],
            VendorFulfillmentStatus::ShippedToCustomer => [
                'Order shipped',
                "Order {$orderNumber} is on the way to the customer.",
            ],
            VendorFulfillmentStatus::Delivered => [
                'Order delivered — escrow released',
                "Order {$orderNumber} was delivered. Earnings moved to your wallet.",
            ],
            default => [
                'Order update',
                "Order {$orderNumber} status: ".($status?->label() ?? 'updated').'.',
            ],
        };

        return [
            'type' => 'fulfillment_'.$status?->value,
            'title' => $title,
            'body' => $body,
            'url' => route('vendor.orders.index', [], false),
            'order_id' => $this->fulfillment->order_id,
            'order_number' => $order?->order_number,
            'status' => $status?->value,
        ];
    }
}
