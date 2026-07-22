<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\User;
use App\Services\CustomerOrderHistory;
use App\Services\VendorListingLimit;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's account hub.
     */
    public function edit(Request $request, CustomerOrderHistory $orders): Response
    {
        $user = $request->user();
        $user->loadMissing('vendorApplication');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'orders' => $orders->forUser($user),
            'shop' => $this->shopSummary($user),
        ]);
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
