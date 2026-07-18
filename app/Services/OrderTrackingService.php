<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\VendorFulfillmentStatus;
use App\Models\Order;
use App\Models\VendorOrderFulfillment;
use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

class OrderTrackingService
{
    public const SESSION_KEY = 'verified_order_ids';

    public function findForLookup(string $orderNumber, string $email): ?Order
    {
        $orderNumber = trim($orderNumber);
        $email = strtolower(trim($email));

        if ($orderNumber === '' || $email === '') {
            return null;
        }

        return Order::query()
            ->whereRaw('LOWER(order_number) = ?', [strtolower($orderNumber)])
            ->where('customer_email', $email)
            ->where('payment_status', PaymentStatus::Paid)
            ->first();
    }

    public function markVerified(Order $order): void
    {
        $ids = session(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            $ids = [];
        }

        $ids[] = $order->id;

        session([self::SESSION_KEY => array_values(array_unique($ids))]);
    }

    public function canView(Request $request, Order $order): bool
    {
        if (! $order->isPaid()) {
            return false;
        }

        $user = $request->user();

        if ($user !== null && $order->user_id === $user->id) {
            return true;
        }

        $verifiedIds = session(self::SESSION_KEY, []);

        if (! is_array($verifiedIds)) {
            return false;
        }

        return in_array($order->id, $verifiedIds, true);
    }

    public function canConfirmReceipt(Order $order): bool
    {
        $order->loadMissing('vendorFulfillments');

        /** @var VendorOrderFulfillment|null $fulfillment */
        $fulfillment = $order->vendorFulfillments->first();

        return $fulfillment?->isShippedToCustomer() ?? false;
    }

    public function confirmReceipt(Order $order): void
    {
        $order->loadMissing('vendorFulfillments');

        /** @var VendorOrderFulfillment|null $fulfillment */
        $fulfillment = $order->vendorFulfillments->first();

        if ($fulfillment === null || ! $fulfillment->markDelivered()) {
            throw new RuntimeException('This order cannot be marked as received yet.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function formatTrackingPayload(Order $order): array
    {
        $order->loadMissing([
            'items',
            'vendorFulfillments.vendor.vendorApplication',
        ]);

        /** @var VendorOrderFulfillment|null $fulfillment */
        $fulfillment = $order->vendorFulfillments->first();
        $shopName = $fulfillment?->vendor?->vendorApplication?->shop_name;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'formatted_total' => $order->formattedTotal(),
            'promo_code' => $order->promo_code,
            'formatted_discount' => $order->discount_cents > 0
                ? 'GHS '.number_format($order->discount_cents / 100, 2)
                : null,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'shipping_address_line1' => $order->shipping_address_line1,
            'shipping_address_line2' => $order->shipping_address_line2,
            'shipping_city' => $order->shipping_city,
            'shipping_region' => $order->shipping_region,
            'placed_at' => ($order->paid_at ?? $order->created_at)?->toIso8601String(),
            'shop_name' => $shopName,
            'current_step_label' => $this->currentStepLabel($fulfillment),
            'can_confirm_receipt' => $this->canConfirmReceipt($order),
            'is_delivered' => $fulfillment?->isDelivered() ?? false,
            'timeline' => $this->timeline($order, $fulfillment),
            'items' => $order->items->map(fn ($item) => [
                'title' => $item->product_title,
                'brand' => $item->product_brand,
                'quantity' => $item->quantity,
                'formatted_line_total' => $item->formattedLineTotal(),
                'image' => PublicStorageUrl::fromStored($item->product_image),
                'attributes' => $item->attributes,
            ])->values()->all(),
        ];
    }

    private function currentStepLabel(?VendorOrderFulfillment $fulfillment): string
    {
        $timeline = $this->timelineSteps($fulfillment);
        $current = collect($timeline)->firstWhere('status', 'current');

        return $current['label'] ?? 'Order confirmed';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeline(Order $order, ?VendorOrderFulfillment $fulfillment): array
    {
        return $this->timelineSteps($fulfillment, $order);
    }

    /**
     * Customer-facing timeline — internal pickup/warehouse steps stay hidden.
     *
     * @return array<int, array<string, mixed>>
     */
    private function timelineSteps(?VendorOrderFulfillment $fulfillment, ?Order $order = null): array
    {
        $currentIndex = match ($fulfillment?->status) {
            VendorFulfillmentStatus::Delivered => 3,
            VendorFulfillmentStatus::ShippedToCustomer => 2,
            null => 1,
            default => 1,
        };

        $outForDeliveryOrLater = in_array($fulfillment?->status, [
            VendorFulfillmentStatus::ShippedToCustomer,
            VendorFulfillmentStatus::Delivered,
        ], true);

        $steps = [
            [
                'key' => 'confirmed',
                'label' => 'Order confirmed',
                'description' => 'Payment received and your order is in our system.',
            ],
            [
                'key' => 'preparing',
                'label' => 'Preparing your order',
                'description' => 'We are getting your items ready for delivery.',
            ],
            [
                'key' => 'shipped',
                'label' => 'Out for delivery',
                'description' => 'Your order is on the way to you.',
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'description' => 'You confirmed that your order arrived.',
            ],
        ];

        $timestamps = [
            0 => $order?->paid_at,
            1 => $outForDeliveryOrLater ? ($fulfillment?->ready_for_pickup_at ?? $order?->paid_at) : null,
            2 => $fulfillment?->shipped_to_customer_at,
            3 => $fulfillment?->delivered_at,
        ];

        return collect($steps)
            ->map(function (array $step, int $index) use ($currentIndex, $timestamps): array {
                $status = $index < $currentIndex
                    ? 'complete'
                    : ($index === $currentIndex ? 'current' : 'upcoming');

                /** @var Carbon|null $completedAt */
                $completedAt = $timestamps[$index] ?? null;

                return [
                    ...$step,
                    'status' => $status,
                    'completed_at' => $completedAt?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
