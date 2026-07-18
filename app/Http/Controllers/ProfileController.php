<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\VendorListingLimit;
use App\Support\PublicStorageUrl;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's account hub.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->loadMissing('vendorApplication');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'orders' => $this->orderHistory($user),
            'shop' => $this->shopSummary($user),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orderHistory(User $user): array
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->latest('id')
            ->limit(25)
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

    /**
     * @return array<string, mixed>|null
     */
    private function shopSummary(User $user): ?array
    {
        $application = $user->vendorApplication;

        if ($application === null) {
            if (! $user->isVendor()) {
                return null;
            }

            return [
                'has_application' => false,
                'shop_name' => null,
                'status' => null,
                'status_label' => null,
                'rejection_reason' => null,
                'logo' => null,
                'phone' => null,
                'category' => null,
                'category_label' => null,
                'shop_slug' => null,
                'storefront_url' => null,
                'product_count' => $user->products()->count(),
                'listing_limit' => null,
            ];
        }

        $categories = StoreVendorApplicationRequest::categories();
        $listingLimit = app(VendorListingLimit::class);
        $storefrontUrl = ($application->isApproved() && $application->shop_slug)
            ? route('shops.show', $application->shop_slug)
            : null;

        return [
            'has_application' => true,
            'shop_name' => $application->shop_name,
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'rejection_reason' => $application->rejection_reason,
            'logo' => $application->shopLogoUrl(),
            'phone' => $application->phone,
            'category' => $application->category,
            'category_label' => $categories[$application->category] ?? $application->category,
            'shop_slug' => $application->shop_slug,
            'storefront_url' => $storefrontUrl,
            'product_count' => $user->products()->count(),
            'listing_limit' => [
                'max' => $listingLimit->maxListingsFor($user),
                'current' => $listingLimit->currentListingCount($user),
                'remaining' => $listingLimit->remainingListings($user),
                'can_add' => $listingLimit->canAddListing($user),
            ],
        ];
    }
}
