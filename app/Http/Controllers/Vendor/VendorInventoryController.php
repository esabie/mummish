<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\VendorListingLimit;
use App\Support\AppLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = $request->query('tab', 'all');
        $search = $request->string('q')->trim()->toString();

        $baseQuery = Product::query()->forVendor($user);

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->active()->count(),
            'draft' => (clone $baseQuery)->draft()->count(),
            'low_stock' => (clone $baseQuery)->lowStock()->count(),
        ];

        $productsQuery = (clone $baseQuery)->search($search !== '' ? $search : null);

        $productsQuery = match ($tab) {
            'active' => $productsQuery->active(),
            'draft' => $productsQuery->draft(),
            'low_stock' => $productsQuery->lowStock(),
            default => $productsQuery,
        };

        $products = $productsQuery
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Product $product) => $this->formatProduct($product));

        $listingLimit = app(VendorListingLimit::class);
        $application = $user->vendorApplication;

        AppLog::debug('[Vendor] Inventory index loaded.', [
            'vendor_user_id' => $user->id,
            'tab' => $tab,
            'search' => $search !== '' ? $search : null,
            'counts' => $counts,
            'listing_limit' => $listingLimit->maxListingsFor($user),
            'listing_current' => $listingLimit->currentListingCount($user),
        ]);

        return Inertia::render('Vendor/Inventory/Index', [
            'shopName' => $application->shop_name,
            'applicationStatus' => $application->status->value,
            'applicationStatusLabel' => $application->status->label(),
            'listingLimit' => [
                'max' => $listingLimit->maxListingsFor($user),
                'current' => $listingLimit->currentListingCount($user),
                'remaining' => $listingLimit->remainingListings($user),
                'can_add' => $listingLimit->canAddListing($user),
            ],
            'filters' => [
                'tab' => $tab,
                'q' => $search,
            ],
            'counts' => $counts,
            'products' => $products,
            'categories' => collect(\App\Http\Requests\StoreVendorApplicationRequest::categories())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->title,
            'sku' => $product->sku ?? '—',
            'category' => $product->category,
            'category_label' => $product->categoryLabel(),
            'price' => $product->formattedPrice(),
            'price_cents' => $product->price_cents,
            'stock_quantity' => $product->stock_quantity,
            'stock_percent' => $product->stockLevelPercent(),
            'is_low_stock' => $product->isLowStock(),
            'is_out_of_stock' => $product->isOutOfStock(),
            'status' => $product->status->value,
            'status_label' => $product->status->label(),
            'image_url' => $product->shopImageUrl(),
        ];
    }
}
