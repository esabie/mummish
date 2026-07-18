<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorNewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public int $vendorMerchandiseCents,
        public int $itemCount,
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
        $amount = 'GHS '.number_format($this->vendorMerchandiseCents / 100, 2);
        $items = $this->itemCount === 1 ? '1 item' : "{$this->itemCount} items";

        return [
            'type' => 'new_order',
            'title' => 'New order received',
            'body' => "Order {$this->order->order_number} — {$items} · {$amount}. Prepare it for pickup.",
            'url' => route('vendor.orders.index', [], false),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
