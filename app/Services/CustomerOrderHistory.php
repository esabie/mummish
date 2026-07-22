<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\PublicStorageUrl;

class CustomerOrderHistory
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 25): array
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (Order $order) {
                $isPaid = $order->payment_status === PaymentStatus::Paid;

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'payment_status' => $order->payment_status->value,
                    'payment_status_label' => $order->payment_status->label(),
                    'formatted_total' => $order->formattedTotal(),
                    'item_count' => $order->items->sum('quantity'),
                    'placed_at' => ($order->paid_at ?? $order->created_at)?->toIso8601String(),
                    'shipping_city' => $order->shipping_city,
                    'shipping_region' => $order->shipping_region,
                    'is_paid' => $isPaid,
                    'receipt_url' => $isPaid ? route('checkout.success', $order) : null,
                    'track_url' => $isPaid ? route('orders.track.show', $order) : null,
                    'items' => $order->items->take(3)->map(fn ($item) => [
                        'title' => $item->product_title,
                        'quantity' => $item->quantity,
                        'image' => PublicStorageUrl::fromStored($item->product_image) ?? $item->product_image,
                        'formatted_line_total' => $item->formattedLineTotal(),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
