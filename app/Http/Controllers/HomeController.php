<?php

namespace App\Http\Controllers;

use App\Enums\VendorApplicationStatus;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\Product;
use App\Models\VendorApplication;
use App\Support\AppLog;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $categories = $this->shopByCategories();
        $featuredStores = $this->featuredStores();
        $popularProducts = $this->popularProducts();

        AppLog::info('[Home] Homepage rendered.', [
            'category_count' => count($categories),
            'featured_store_count' => count($featuredStores),
            'popular_product_count' => count($popularProducts),
        ]);

        return Inertia::render('Welcome', [
            'categories' => $categories,
            'featured_stores' => $featuredStores,
            'popular_products' => $popularProducts,
        ]);
    }

    /**
     * @return array<int, array{id: string, label: string, count: int, image: string}>
     */
    private function shopByCategories(): array
    {
        $categoryCounts = Product::query()
            ->visibleInShop()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $images = config('marketplace.homepage_category_images', []);
        $placeholder = (string) config('marketplace.product_placeholder_image');

        return collect(StoreVendorApplicationRequest::categories())
            ->map(fn (string $label, string $id) => [
                'id' => $id,
                'label' => $label,
                'count' => (int) ($categoryCounts[$id] ?? 0),
                'image' => $this->categoryImageUrl($id, $images, $placeholder),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $configuredImages
     */
    private function categoryImageUrl(string $id, array $configuredImages, string $placeholder): string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $path = public_path("images/categories/{$id}.{$extension}");

            if (is_readable($path)) {
                return asset("images/categories/{$id}.{$extension}");
            }
        }

        return $configuredImages[$id] ?? $placeholder;
    }

    /**
     * @return array<int, array{slug: string, name: string, initial: string, category: string, product_count: int, image: string|null}>
     */
    private function featuredStores(): array
    {
        $stores = VendorApplication::query()
            ->where('status', VendorApplicationStatus::Approved)
            ->whereNotNull('shop_slug')
            ->with([
                'user.products' => fn ($query) => $query->visibleInShop()->latest('id')->limit(1),
            ])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(16)
            ->get();

        if ($stores->isEmpty()) {
            return [];
        }

        $productCounts = Product::query()
            ->visibleInShop()
            ->whereIn('user_id', $stores->pluck('user_id'))
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        $categoryLabels = StoreVendorApplicationRequest::categories();

        return $stores
            ->map(function (VendorApplication $store) use ($productCounts, $categoryLabels) {
                $featuredProduct = $store->user?->products->first();
                $productCount = (int) ($productCounts[$store->user_id] ?? 0);

                return [
                    'slug' => (string) $store->shop_slug,
                    'name' => $store->shop_name,
                    'initial' => strtoupper(substr($store->shop_name, 0, 1)),
                    'category' => $categoryLabels[$store->category] ?? $store->category,
                    'product_count' => $productCount,
                    'image' => $store->shopLogoUrl() ?? $featuredProduct?->shopImageUrl(),
                ];
            })
            ->sortByDesc('product_count')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function popularProducts(): array
    {
        return Product::query()
            ->visibleInShop()
            ->with(['user.vendorApplication'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Product $product) => $product->toShopCatalogItem())
            ->values()
            ->all();
    }
}
