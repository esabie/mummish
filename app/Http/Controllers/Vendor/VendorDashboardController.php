<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\VendorEarningsService;
use App\Services\VendorListingLimit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $application = $user->vendorApplication;
        $listingLimit = app(VendorListingLimit::class);

        $productQuery = Product::query()->forVendor($user);
        $earnings = app(VendorEarningsService::class)->dashboardSummary($user);

        return Inertia::render('Vendor/Dashboard', [
            'shopName' => $application->shop_name,
            'applicationStatus' => $application->status->value,
            'applicationStatusLabel' => $application->status->label(),
            'rejectionReason' => $application->rejection_reason,
            'listingLimit' => [
                'max' => $listingLimit->maxListingsFor($user),
                'current' => $listingLimit->currentListingCount($user),
                'remaining' => $listingLimit->remainingListings($user),
                'can_add' => $listingLimit->canAddListing($user),
            ],
            'stats' => [
                'total_products' => (clone $productQuery)->count(),
                'active_products' => (clone $productQuery)->where('status', ProductStatus::Active)->count(),
                'draft_products' => (clone $productQuery)->where('status', ProductStatus::Draft)->count(),
                'low_stock_products' => (clone $productQuery)->lowStock()->count(),
            ],
            'earnings' => $earnings,
        ]);
    }
}
