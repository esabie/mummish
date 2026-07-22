<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PromoCostBearer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorOrderFulfillment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VendorEarningsService
{
    public function commissionBps(): int
    {
        return max(0, (int) config('marketplace.vendor_commission_bps', 1000));
    }

    public function commissionPercent(): int
    {
        return (int) round($this->commissionBps() / 100);
    }

    /**
     * Split merchandise gross into Mummish commission and vendor payout,
     * optionally absorbing a promo discount from the chosen cost bearer(s).
     *
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int, discount_cents: int}
     */
    public function splitAmount(
        int $grossCents,
        int $discountCents = 0,
        ?PromoCostBearer $costBearer = null,
    ): array {
        $grossCents = max(0, $grossCents);
        $discountCents = max(0, min($discountCents, $grossCents));
        $commissionCents = (int) floor($grossCents * $this->commissionBps() / 10000);
        $payoutCents = $grossCents - $commissionCents;

        if ($discountCents > 0 && $costBearer !== null) {
            [$fromCommission, $fromPayout] = $this->allocateDiscount(
                $discountCents,
                $commissionCents,
                $payoutCents,
                $costBearer,
            );
            $commissionCents -= $fromCommission;
            $payoutCents -= $fromPayout;
        }

        return [
            'gross_cents' => $grossCents,
            'commission_cents' => $commissionCents,
            'payout_cents' => $payoutCents,
            'discount_cents' => $discountCents,
        ];
    }

    /**
     * Split an order item using its parent order’s promo snapshot.
     *
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int, discount_cents: int}
     */
    public function splitOrderItem(OrderItem $item): array
    {
        $order = $item->order;
        $orderGross = $this->orderMerchandiseGrossCents($order);
        $itemGross = (int) $item->line_total_cents;

        if ($orderGross < 1 || $itemGross < 1) {
            return $this->splitAmount(0);
        }

        $orderDiscount = (int) ($order?->discount_cents ?? 0);
        $costBearer = $order?->promo_cost_bearer;

        // Single-item (or full-order) path — no rounding ambiguity.
        if ($itemGross === $orderGross) {
            return $this->splitAmount($itemGross, $orderDiscount, $costBearer);
        }

        // Prorate the order-level discount across line items by share of gross.
        $itemDiscount = (int) floor($orderDiscount * $itemGross / $orderGross);

        return $this->splitAmount($itemGross, $itemDiscount, $costBearer);
    }

    /**
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int, discount_cents: int}
     */
    public function splitOrderMerchandise(Order $order, ?int $vendorUserId = null): array
    {
        $allItems = $order->relationLoaded('items')
            ? $order->items
            : $order->items()->get();

        $items = $vendorUserId !== null
            ? $allItems->where('vendor_user_id', $vendorUserId)
            : $allItems;

        $grossCents = (int) $items->sum('line_total_cents');

        // Promo discount is order-level; attribute it when summing the full order
        // or the sole vendor on the order (carts are single-vendor).
        $discountCents = $vendorUserId === null || $items->count() === $allItems->count()
            ? (int) $order->discount_cents
            : 0;

        return $this->splitAmount($grossCents, $discountCents, $order->promo_cost_bearer);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(User $vendor): array
    {
        $items = OrderItem::query()
            ->with(['order'])
            ->where('vendor_user_id', $vendor->id)
            ->whereHas('order', fn (Builder $query) => $query->where('payment_status', PaymentStatus::Paid))
            ->get();

        $summary = $this->summarizeItems($items);
        $fulfillments = $this->fulfillmentsForItems($items);

        $summary['recent_sales'] = $items
            ->sortByDesc(fn (OrderItem $item) => $item->order?->paid_at ?? $item->created_at)
            ->take(5)
            ->map(function (OrderItem $item) use ($fulfillments) {
                $split = $this->splitOrderItem($item);
                $fulfillment = $fulfillments->get($item->order_id);
                $released = $fulfillment?->releasesEscrow() ?? false;
                $settled = $item->order?->isVendorPaid() ?? false;

                [$status, $statusLabel] = match (true) {
                    $settled => ['settled', 'Paid out'],
                    $released => ['released', 'Available in wallet'],
                    default => ['escrow', 'In escrow'],
                };

                return [
                    'order_number' => $item->order?->order_number,
                    'product_title' => $item->product_title,
                    'paid_at' => $item->order?->paid_at?->toIso8601String(),
                    'gross_cents' => $split['gross_cents'],
                    'commission_cents' => $split['commission_cents'],
                    'payout_cents' => $split['payout_cents'],
                    'formatted_gross' => $this->formatCents($split['gross_cents']),
                    'formatted_commission' => $this->formatCents($split['commission_cents']),
                    'formatted_payout' => $this->formatCents($split['payout_cents']),
                    'status' => $status,
                    'status_label' => $statusLabel,
                ];
            })
            ->values()
            ->all();

        return $summary;
    }

    /**
     * Platform-wide earnings summary for paid order line items.
     *
     * Merchandise (item subtotals) is split commission vs vendor payout.
     * Delivery fees are tracked separately and are never part of that split.
     *
     * @return array<string, mixed>
     */
    public function platformSummary(Collection $items): array
    {
        $items = $items->loadMissing(['order', 'vendor.vendorApplication']);

        $summary = $this->summarizeItems($items);

        $summary['vendor_breakdown'] = $items
            ->groupBy('vendor_user_id')
            ->map(function (Collection $group): array {
                /** @var OrderItem $first */
                $first = $group->first();
                $vendor = $first->vendor;
                $bucket = $this->rawBucket();

                foreach ($group->groupBy('order_id') as $orderItems) {
                    $split = $this->splitOrderMerchandiseGroup($orderItems);
                    $bucket['gross_cents'] += $split['gross_cents'];
                    $bucket['commission_cents'] += $split['commission_cents'];
                    $bucket['payout_cents'] += $split['payout_cents'];
                }

                $bucket['order_count'] = $group->pluck('order_id')->unique()->count();

                return array_merge([
                    'shop_name' => $vendor?->vendorApplication?->shop_name
                        ?? $vendor?->email
                        ?? 'Unknown vendor',
                    'vendor_email' => $vendor?->email,
                ], $this->formatBucket($bucket));
            })
            ->sortBy('shop_name')
            ->values()
            ->all();

        $summary['commission_share_percent'] = $summary['totals']['gross_cents'] > 0
            ? (int) round($summary['totals']['commission_cents'] / $summary['totals']['gross_cents'] * 100)
            : 0;

        $summary['payout_share_percent'] = $summary['totals']['gross_cents'] > 0
            ? 100 - $summary['commission_share_percent']
            : 0;

        $summary['delivery'] = $this->deliveryTotals($items);
        $discountCents = $this->totalDiscountCents($items);
        $collectedMerchandise = $summary['totals']['gross_cents'] - $discountCents;
        $summary['collected'] = [
            'merchandise_cents' => $collectedMerchandise,
            'shipping_cents' => $summary['delivery']['shipping_cents'],
            'total_cents' => $collectedMerchandise + $summary['delivery']['shipping_cents'],
            'formatted_merchandise' => $this->formatCents($collectedMerchandise),
            'formatted_shipping' => $summary['delivery']['formatted_shipping'],
            'formatted_total' => $this->formatCents(
                $collectedMerchandise + $summary['delivery']['shipping_cents']
            ),
        ];

        return $summary;
    }

    /**
     * Sum shipping once per paid order (never per line item).
     *
     * @param  Collection<int, OrderItem>  $items
     * @return array{
     *     shipping_cents: int,
     *     order_count: int,
     *     formatted_shipping: string,
     *     due_cents: int,
     *     due_order_count: int,
     *     formatted_due: string,
     *     paid_cents: int,
     *     paid_order_count: int,
     *     formatted_paid: string
     * }
     */
    private function deliveryTotals(Collection $items): array
    {
        $orders = $items
            ->pluck('order')
            ->filter()
            ->unique('id')
            ->values();

        $withShipping = $orders->filter(fn (Order $order) => (int) $order->shipping_cents > 0);
        $dueOrders = $withShipping->filter(fn (Order $order) => $order->courier_paid_at === null);
        $paidOrders = $withShipping->filter(fn (Order $order) => $order->courier_paid_at !== null);

        $shippingCents = (int) $withShipping->sum(fn (Order $order) => (int) $order->shipping_cents);
        $dueCents = (int) $dueOrders->sum(fn (Order $order) => (int) $order->shipping_cents);
        $paidCents = (int) $paidOrders->sum(fn (Order $order) => (int) $order->shipping_cents);

        return [
            'shipping_cents' => $shippingCents,
            'order_count' => $withShipping->count(),
            'formatted_shipping' => $this->formatCents($shippingCents),
            'due_cents' => $dueCents,
            'due_order_count' => $dueOrders->count(),
            'formatted_due' => $this->formatCents($dueCents),
            'paid_cents' => $paidCents,
            'paid_order_count' => $paidOrders->count(),
            'formatted_paid' => $this->formatCents($paidCents),
        ];
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function totalDiscountCents(Collection $items): int
    {
        return (int) $items
            ->pluck('order')
            ->filter()
            ->unique('id')
            ->sum(fn (Order $order) => (int) $order->discount_cents);
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return array<string, mixed>
     */
    public function summarizeItems(Collection $items): array
    {
        $fulfillments = $this->fulfillmentsForItems($items);

        $totals = $this->rawBucket();
        $escrow = $this->rawBucket();
        $wallet = $this->rawBucket();
        $walletDue = $this->rawBucket();
        $walletSettled = $this->rawBucket();
        $seenOrders = [];

        foreach ($items->groupBy('order_id') as $orderItems) {
            $split = $this->splitOrderMerchandiseGroup($orderItems);
            $totals['gross_cents'] += $split['gross_cents'];
            $totals['commission_cents'] += $split['commission_cents'];
            $totals['payout_cents'] += $split['payout_cents'];

            /** @var OrderItem $first */
            $first = $orderItems->first();
            $orderId = $first->order_id;
            $order = $first->order;

            if (! in_array($orderId, $seenOrders, true)) {
                $seenOrders[] = $orderId;
                $totals['order_count']++;
            }

            $fulfillment = $fulfillments->get($orderId);
            $released = $fulfillment?->releasesEscrow() ?? false;

            if ($released) {
                $wallet['gross_cents'] += $split['gross_cents'];
                $wallet['commission_cents'] += $split['commission_cents'];
                $wallet['payout_cents'] += $split['payout_cents'];

                if ($order?->isVendorPaid()) {
                    $walletSettled['gross_cents'] += $split['gross_cents'];
                    $walletSettled['commission_cents'] += $split['commission_cents'];
                    $walletSettled['payout_cents'] += $split['payout_cents'];
                } else {
                    $walletDue['gross_cents'] += $split['gross_cents'];
                    $walletDue['commission_cents'] += $split['commission_cents'];
                    $walletDue['payout_cents'] += $split['payout_cents'];
                }
            } else {
                $escrow['gross_cents'] += $split['gross_cents'];
                $escrow['commission_cents'] += $split['commission_cents'];
                $escrow['payout_cents'] += $split['payout_cents'];
            }
        }

        $escrowOrderIds = $items
            ->filter(fn (OrderItem $item) => ! ($fulfillments->get($item->order_id)?->releasesEscrow() ?? false))
            ->pluck('order_id')
            ->unique();
        $walletOrderIds = $items
            ->filter(fn (OrderItem $item) => $fulfillments->get($item->order_id)?->releasesEscrow() ?? false)
            ->pluck('order_id')
            ->unique();
        $walletDueOrderIds = $items
            ->filter(fn (OrderItem $item) => ($fulfillments->get($item->order_id)?->releasesEscrow() ?? false)
                && ! ($item->order?->isVendorPaid() ?? false))
            ->pluck('order_id')
            ->unique();
        $walletSettledOrderIds = $items
            ->filter(fn (OrderItem $item) => ($fulfillments->get($item->order_id)?->releasesEscrow() ?? false)
                && ($item->order?->isVendorPaid() ?? false))
            ->pluck('order_id')
            ->unique();

        $escrow['order_count'] = $escrowOrderIds->count();
        $wallet['order_count'] = $walletOrderIds->count();
        $walletDue['order_count'] = $walletDueOrderIds->count();
        $walletSettled['order_count'] = $walletSettledOrderIds->count();

        return [
            'commission_percent' => $this->commissionPercent(),
            'totals' => $this->formatBucket($totals),
            'escrow' => $this->formatBucket($escrow),
            'wallet' => $this->formatBucket($wallet),
            'wallet_due' => $this->formatBucket($walletDue),
            'wallet_settled' => $this->formatBucket($walletSettled),
        ];
    }

    /**
     * @param  Collection<int, OrderItem>  $orderItems  Items for a single order (optionally one vendor).
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int, discount_cents: int}
     */
    private function splitOrderMerchandiseGroup(Collection $orderItems): array
    {
        /** @var OrderItem $first */
        $first = $orderItems->first();
        $order = $first->order;
        $grossCents = (int) $orderItems->sum('line_total_cents');
        $discountCents = (int) ($order?->discount_cents ?? 0);

        return $this->splitAmount($grossCents, $discountCents, $order?->promo_cost_bearer);
    }

    private function orderMerchandiseGrossCents(?Order $order): int
    {
        if ($order === null) {
            return 0;
        }

        if ($order->relationLoaded('items')) {
            return (int) $order->items->sum('line_total_cents');
        }

        return (int) $order->subtotal_cents;
    }

    /**
     * @return array{0: int, 1: int} [fromCommission, fromPayout]
     */
    private function allocateDiscount(
        int $discountCents,
        int $commissionCents,
        int $payoutCents,
        PromoCostBearer $costBearer,
    ): array {
        [$wantCommission, $wantPayout] = match ($costBearer) {
            PromoCostBearer::Mummish => [$discountCents, 0],
            PromoCostBearer::Vendor => [0, $discountCents],
            PromoCostBearer::Both => [
                (int) floor($discountCents / 2),
                (int) ceil($discountCents / 2),
            ],
        };

        $fromCommission = min($wantCommission, $commissionCents);
        $fromPayout = min($wantPayout, $payoutCents);
        $remainder = ($wantCommission - $fromCommission) + ($wantPayout - $fromPayout);

        // If one side cannot absorb its share (e.g. discount exceeds commission),
        // spill the leftover onto the other side so the collected cash still balances.
        if ($remainder > 0) {
            $extraPayout = min($remainder, $payoutCents - $fromPayout);
            $fromPayout += $extraPayout;
            $remainder -= $extraPayout;
        }

        if ($remainder > 0) {
            $extraCommission = min($remainder, $commissionCents - $fromCommission);
            $fromCommission += $extraCommission;
        }

        return [$fromCommission, $fromPayout];
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int|string, VendorOrderFulfillment>
     */
    private function fulfillmentsForItems(Collection $items): Collection
    {
        $orderIds = $items->pluck('order_id')->unique();

        if ($orderIds->isEmpty()) {
            return collect();
        }

        return VendorOrderFulfillment::query()
            ->whereIn('order_id', $orderIds)
            ->get()
            ->keyBy('order_id');
    }

    /**
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int, order_count: int}
     */
    private function rawBucket(): array
    {
        return [
            'gross_cents' => 0,
            'commission_cents' => 0,
            'payout_cents' => 0,
            'order_count' => 0,
        ];
    }

    /**
     * @param  array{gross_cents: int, commission_cents: int, payout_cents: int, order_count: int}  $bucket
     * @return array<string, mixed>
     */
    private function formatBucket(array $bucket): array
    {
        return [
            'gross_cents' => $bucket['gross_cents'],
            'commission_cents' => $bucket['commission_cents'],
            'payout_cents' => $bucket['payout_cents'],
            'order_count' => $bucket['order_count'],
            'formatted_gross' => $this->formatCents($bucket['gross_cents']),
            'formatted_commission' => $this->formatCents($bucket['commission_cents']),
            'formatted_payout' => $this->formatCents($bucket['payout_cents']),
        ];
    }

    private function formatCents(int $cents): string
    {
        return 'GHS '.number_format($cents / 100, 2);
    }
}
