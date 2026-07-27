<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorPayoutDetailsRequest;
use App\Models\Product;
use App\Services\VendorEarningsService;
use App\Services\VendorListingLimit;
use Illuminate\Http\RedirectResponse;
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
            'payoutDetails' => [
                'payment_method' => $application->payment_method,
                'payment_method_label' => $application->paymentMethodLabel(),
                'bank_name' => $application->bank_name,
                'bank_account_name' => $application->bank_account_name,
                'bank_account_number' => $application->bank_account_number,
                'mobile_money_provider' => $application->mobile_money_provider,
                'mobile_money_name' => $application->mobile_money_name,
                'mobile_money_number' => $application->mobile_money_number,
            ],
            'ghanaBanks' => config('ghana_banks.names', []),
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

    public function updatePayoutDetails(UpdateVendorPayoutDetailsRequest $request): RedirectResponse
    {
        $application = $request->user()->vendorApplication;

        if ($application->hasPaymentDetails()) {
            return back()->with('error', 'Payment details are locked. Contact Mummish support if you need them changed.');
        }

        $application->applyPayoutDetails($request->validated());

        return back()->with('success', 'Payment details saved. Contact Mummish support if you need them changed later.');
    }
}
