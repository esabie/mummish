<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\VendorFulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendAdminVendorOrderReadyForPickupSms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorOrderFulfillment;
use App\Support\AppLog;
use App\Support\PublicStorageUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $vendorId = $request->user()->id;

        $items = OrderItem::query()
            ->with(['order' => fn ($query) => $query->where('payment_status', 'paid')])
            ->where('vendor_user_id', $vendorId)
            ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
            ->latest('id')
            ->limit(100)
            ->get();

        $orderIds = $items->pluck('order_id')->unique();

        $fulfillments = VendorOrderFulfillment::query()
            ->where('vendor_user_id', $vendorId)
            ->whereIn('order_id', $orderIds)
            ->get()
            ->keyBy('order_id');

        $orders = $items
            ->groupBy('order_id')
            ->map(function ($group) use ($fulfillments) {
                /** @var \Illuminate\Support\Collection<int, OrderItem> $group */
                $order = $group->first()->order;
                $lineTotal = $group->sum('line_total_cents');
                $fulfillment = $fulfillments->get($order->id);

                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'placed_at' => $order->paid_at?->toIso8601String() ?? $order->created_at?->toIso8601String(),
                    'formatted_total' => 'GHS '.number_format($lineTotal / 100, 2),
                    'item_count' => $group->sum('quantity'),
                    'status' => $fulfillment?->status?->value ?? null,
                    'status_label' => $fulfillment?->status?->label() ?? null,
                    'is_ready_for_pickup' => $fulfillment !== null,
                    'ready_for_pickup_at' => $fulfillment?->ready_for_pickup_at?->toIso8601String(),
                    'items' => $group->map(fn (OrderItem $item) => [
                        'title' => $item->product_title,
                        'brand' => $item->product_brand,
                        'quantity' => $item->quantity,
                        'formatted_line_total' => $item->formattedLineTotal(),
                        // Stored URLs may point at an old host; rebuild for the current one.
                        'image' => PublicStorageUrl::fromStored($item->product_image),
                        'attributes' => $item->attributes,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->sortByDesc('placed_at')
            ->values()
            ->all();

        $newCount = collect($orders)->where('is_ready_for_pickup', false)->count();
        $readyCount = collect($orders)->where('is_ready_for_pickup', true)->count();

        AppLog::debug('[Vendor] Orders index loaded.', [
            'vendor_user_id' => $vendorId,
            'order_count' => count($orders),
            'new_count' => $newCount,
            'ready_count' => $readyCount,
        ]);

        return Inertia::render('Vendor/Orders/Index', [
            'shopName' => $request->user()->vendorApplication->shop_name,
            'orders' => $orders,
            'counts' => [
                'new' => $newCount,
                'ready' => $readyCount,
            ],
        ]);
    }

    public function fulfill(Request $request, Order $order): RedirectResponse
    {
        $vendorId = $request->user()->id;

        $ownsItems = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('vendor_user_id', $vendorId)
            ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
            ->exists();

        if (! $ownsItems) {
            AppLog::warning('[Vendor] Fulfill denied — vendor does not own order items.', [
                'vendor_user_id' => $vendorId,
                'order_id' => $order->id,
            ]);

            abort(403);
        }

        AppLog::info('[Vendor] Marking order ready for pickup.', [
            'vendor_user_id' => $vendorId,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        VendorOrderFulfillment::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'vendor_user_id' => $vendorId,
            ],
            [
                'status' => VendorFulfillmentStatus::ReadyForPickup,
                'ready_for_pickup_at' => now(),
                'fulfilled_at' => now(),
            ]
        );

        SendAdminVendorOrderReadyForPickupSms::dispatch($order->id, $vendorId);

        return back()->with('success', 'Order marked as ready for pickup.');
    }
}
