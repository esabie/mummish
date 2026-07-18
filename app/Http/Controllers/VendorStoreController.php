<?php

namespace App\Http\Controllers;

use App\Enums\VendorApplicationStatus;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\Product;
use App\Models\VendorApplication;
use App\Support\AppLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorStoreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $perPage = max(1, min((int) config('marketplace.stores_per_page', 24), 48));

        AppLog::info('[VendorStore] Stores directory requested.', [
            'search_query' => $query !== '' ? $query : null,
        ]);

        $paginator = VendorApplication::query()
            ->where('status', VendorApplicationStatus::Approved)
            ->whereNotNull('shop_slug')
            ->when($query !== '', fn ($q) => $q->where('shop_name', 'like', "%{$query}%"))
            ->with([
                'user.products' => fn ($q) => $q->visibleInShop()->latest('id')->limit(1),
            ])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $productCounts = Product::query()
            ->visibleInShop()
            ->whereIn('user_id', $paginator->getCollection()->pluck('user_id'))
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        $categoryLabels = StoreVendorApplicationRequest::categories();

        $stores = $paginator
            ->getCollection()
            ->map(fn (VendorApplication $store) => [
                'slug' => (string) $store->shop_slug,
                'name' => $store->shop_name,
                'initial' => strtoupper(substr($store->shop_name, 0, 1)),
                'category' => $categoryLabels[$store->category] ?? $store->category,
                'product_count' => (int) ($productCounts[$store->user_id] ?? 0),
                'image' => $store->shopLogoUrl() ?? $store->user?->products->first()?->shopImageUrl(),
            ])
            ->sortByDesc('product_count')
            ->values()
            ->all();

        return Inertia::render('Shop/StoresDirectory', [
            'search_query' => $query,
            'stores' => $stores,
            'result_count' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => max(1, $paginator->lastPage()),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $store = $this->resolveStorefront($slug);
        $query = trim((string) $request->query('q', ''));

        AppLog::info('[VendorStore] Storefront requested.', [
            'store_slug' => $slug,
            'store_id' => $store->id,
            'vendor_user_id' => $store->user_id,
            'search_query' => $query !== '' ? $query : null,
        ]);

        $perPage = max(1, min((int) config('marketplace.shop_per_page', 12), 48));

        $paginator = Product::query()
            ->visibleInShop()
            ->where('user_id', $store->user_id)
            ->with(['user.vendorApplication'])
            ->search($query !== '' ? $query : null)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        AppLog::debug('[VendorStore] Storefront results.', [
            'store_slug' => $slug,
            'result_count' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
        ]);

        return Inertia::render('Shop/VendorStore', [
            'store' => $this->formatStore($store),
            'search_query' => $query,
            'result_count' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => max(1, $paginator->lastPage()),
            'items' => $paginator
                ->getCollection()
                ->map(fn (Product $product) => $product->toShopCatalogItem())
                ->values()
                ->all(),
        ]);
    }

    public function product(string $slug, int $id): Response
    {
        AppLog::info('[VendorStore] Store product detail requested.', [
            'store_slug' => $slug,
            'product_id' => $id,
        ]);

        $store = $this->resolveStorefront($slug);

        $product = Product::query()
            ->visibleInShop()
            ->where('user_id', $store->user_id)
            ->with(['user.vendorApplication'])
            ->find($id);

        if ($product === null) {
            AppLog::warning('[VendorStore] Product not found in store.', [
                'store_slug' => $slug,
                'product_id' => $id,
            ]);

            abort(404);
        }

        $shopController = app(ShopController::class);

        return Inertia::render('Shop/Show', [
            'product' => $shopController->formatProductForPage($product->toShopCatalogItem()),
            'store' => $this->formatStore($store),
        ]);
    }

    private function resolveStorefront(string $slug): VendorApplication
    {
        $store = VendorApplication::query()
            ->where('shop_slug', $slug)
            ->where('status', VendorApplicationStatus::Approved)
            ->whereNotNull('user_id')
            ->first();

        if ($store === null) {
            AppLog::warning('[VendorStore] Storefront not found.', ['store_slug' => $slug]);

            abort(404);
        }

        return $store;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStore(VendorApplication $store): array
    {
        return [
            'slug' => $store->shop_slug,
            'name' => $store->shop_name,
            'category' => $store->category,
            'initial' => strtoupper(substr($store->shop_name, 0, 1)),
            'logo' => $store->shopLogoUrl(),
            'url' => url()->route('shops.show', $store->shop_slug),
        ];
    }
}
