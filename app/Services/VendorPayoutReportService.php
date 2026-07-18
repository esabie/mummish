<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorPayoutReportService
{
    public function paidOrderItemsQuery(): Builder
    {
        return OrderItem::query()
            ->with(['order', 'vendor.vendorApplication'])
            ->whereHas('order', fn (Builder $query) => $query->where('payment_status', PaymentStatus::Paid));
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int, array{shop_name: string, vendor_email: ?string, quantity: int, total_cents: int, order_count: int}>
     */
    public function vendorTotals(Collection $items): Collection
    {
        return $items
            ->groupBy('vendor_user_id')
            ->map(function (Collection $group): array {
                /** @var OrderItem $first */
                $first = $group->first();
                $vendor = $first->vendor;

                return [
                    'shop_name' => $vendor?->vendorApplication?->shop_name
                        ?? $vendor?->email
                        ?? 'Unknown vendor',
                    'vendor_email' => $vendor?->email,
                    'quantity' => (int) $group->sum('quantity'),
                    'total_cents' => (int) $group->sum('line_total_cents'),
                    'order_count' => $group->pluck('order_id')->unique()->count(),
                ];
            })
            ->sortBy('shop_name')
            ->values();
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    public function csvDownloadResponse(Collection $items, string $filename = 'vendor-payouts.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($items): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Order #',
                'Paid date',
                'Shop',
                'Vendor email',
                'Product',
                'SKU',
                'Qty',
                'Unit price (GHS)',
                'Line total (GHS)',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, $this->lineItemRow($item));
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Vendor summary']);
            fputcsv($handle, [
                'Shop',
                'Vendor email',
                'Qty sold',
                'Orders',
                'Payout due (GHS)',
            ]);

            foreach ($this->vendorTotals($items) as $total) {
                fputcsv($handle, [
                    $total['shop_name'],
                    $total['vendor_email'] ?? '',
                    $total['quantity'],
                    $total['order_count'],
                    number_format($total['total_cents'] / 100, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string|int>
     */
    private function lineItemRow(OrderItem $item): array
    {
        $vendor = $item->vendor;

        return [
            $item->order?->order_number ?? '',
            $item->order?->paid_at?->format('Y-m-d H:i') ?? '',
            $vendor?->vendorApplication?->shop_name ?? $vendor?->email ?? '',
            $vendor?->email ?? '',
            $item->product_title,
            $item->product_sku ?? '',
            $item->quantity,
            number_format($item->unit_price_cents / 100, 2, '.', ''),
            number_format($item->line_total_cents / 100, 2, '.', ''),
        ];
    }
}
