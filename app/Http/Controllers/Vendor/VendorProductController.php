<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductCondition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductImageService;
use App\Services\VendorListingLimit;
use App\Support\AppLog;
use App\Support\PublicStorageUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VendorProductController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $listingLimit = app(VendorListingLimit::class);

        if (! $listingLimit->canAddListing($request->user())) {
            AppLog::warning('[Vendor] Product create blocked — listing limit reached.', [
                'vendor_user_id' => $request->user()->id,
            ]);

            return redirect()
                ->route('vendor.inventory.index')
                ->with('error', $listingLimit->limitMessage($request->user()));
        }

        return Inertia::render('Vendor/Inventory/Form', $this->formProps($request, null));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $listingLimit = app(VendorListingLimit::class);

        if (! $listingLimit->canAddListing($request->user())) {
            AppLog::warning('[Vendor] Product store blocked — listing limit reached.', [
                'vendor_user_id' => $request->user()->id,
            ]);

            throw ValidationException::withMessages([
                'title' => $listingLimit->limitMessage($request->user()),
            ]);
        }

        $validated = $request->validated();
        $imageUrls = $this->resolveImageUrls($request);

        AppLog::info('[Vendor] Creating product.', [
            'vendor_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'condition' => $validated['condition'],
            'status' => $validated['status'],
            'stock_quantity' => $validated['stock_quantity'],
            'image_count' => count($imageUrls),
        ]);

        $product = Product::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'sku' => $this->generateSku($request->user()->id),
            'category' => $validated['category'],
            'brand' => $validated['brand'],
            'condition' => $validated['condition'],
            'clothing_size' => $this->resolveClothingSize($validated),
            'price_cents' => $this->priceToCents($validated['price']),
            'compare_at_price_cents' => $this->compareAtPriceToCents($validated['compare_at_price'] ?? null),
            'stock_quantity' => $validated['stock_quantity'],
            'status' => $validated['status'],
            'image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'material_tags' => $validated['material_tags'],
            'allows_customization' => (bool) $validated['allows_customization'],
        ]);

        AppLog::info('[Vendor] Product created.', [
            'product_id' => $product->id,
            'vendor_user_id' => $request->user()->id,
            'sku' => $product->sku,
        ]);

        return redirect()
            ->route('vendor.inventory.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->ensureOwnsProduct($request, $product);

        return Inertia::render('Vendor/Inventory/Form', $this->formProps($request, $product));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureOwnsProduct($request, $product);

        $validated = $request->validated();
        $imageUrls = $this->resolveImageUrls($request, $product);

        AppLog::info('[Vendor] Updating product.', [
            'product_id' => $product->id,
            'vendor_user_id' => $request->user()->id,
            'status' => $validated['status'],
            'stock_quantity' => $validated['stock_quantity'],
            'image_count' => count($imageUrls),
        ]);

        $product->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'brand' => $validated['brand'],
            'condition' => $validated['condition'],
            'clothing_size' => $this->resolveClothingSize($validated),
            'price_cents' => $this->priceToCents($validated['price']),
            'compare_at_price_cents' => $this->compareAtPriceToCents($validated['compare_at_price'] ?? null),
            'stock_quantity' => $validated['stock_quantity'],
            'status' => $validated['status'],
            'image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'material_tags' => $validated['material_tags'],
            'allows_customization' => (bool) $validated['allows_customization'],
        ]);

        AppLog::info('[Vendor] Product updated.', [
            'product_id' => $product->id,
            'vendor_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('vendor.inventory.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureOwnsProduct($request, $product);

        AppLog::info('[Vendor] Deleting product.', [
            'product_id' => $product->id,
            'vendor_user_id' => $request->user()->id,
            'sku' => $product->sku,
        ]);

        $product->delete();

        return redirect()
            ->route('vendor.inventory.index')
            ->with('success', 'Product removed.');
    }

    private function ensureOwnsProduct(Request $request, Product $product): void
    {
        if ($product->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Product $product): array
    {
        $listingLimit = app(VendorListingLimit::class);
        $application = $request->user()->vendorApplication;

        return [
            'product' => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'description' => $product->description !== null
                    ? trim(preg_replace('/\p{N}/u', '', strip_tags($product->description)) ?? '')
                    : '',
                'sku' => $product->sku,
                'category' => $product->category,
                'brand' => $product->brand ?? '',
                'condition' => $product->condition?->value ?? ProductCondition::New->value,
                'clothing_size' => $product->clothing_size ?? '',
                'price' => number_format($product->price_cents / 100, 2, '.', ''),
                'compare_at_price' => $product->compare_at_price_cents !== null
                    ? number_format($product->compare_at_price_cents / 100, 2, '.', '')
                    : '',
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status->value,
                'image_url' => $product->image_url ?? '',
                'image_urls' => collect($product->image_urls ?? ($product->image_url ? [$product->image_url] : []))
                    ->map(fn ($url) => PublicStorageUrl::fromStored(is_string($url) ? $url : null))
                    ->filter()
                    ->values()
                    ->all(),
                'material_tags' => $product->material_tags ?? [],
                'allows_customization' => (bool) $product->allows_customization,
            ] : null,
            'materialTagOptions' => collect(config('marketplace.product_material_tags', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'marketplaceName' => config('app.name', 'Mummish'),
            'categories' => collect(\App\Http\Requests\StoreVendorApplicationRequest::categories())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'clothingSizeOptions' => collect(config('marketplace.clothing_sizes', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'draft', 'label' => 'Draft'],
            ],
            'conditionOptions' => ProductCondition::options(),
            'listingLimit' => [
                'max' => $listingLimit->maxListingsFor($request->user()),
                'current' => $listingLimit->currentListingCount($request->user()),
                'remaining' => $listingLimit->remainingListings($request->user()),
                'can_add' => $listingLimit->canAddListing($request->user()),
            ],
            'shopName' => $application->shop_name,
            'minImages' => (int) config('marketplace.min_product_images', 3),
            'maxImages' => (int) config('marketplace.max_product_images', 8),
            'imageRequirements' => [
                'minWidth' => (int) config('marketplace.product_image_min_width', 800),
                'minHeight' => (int) config('marketplace.product_image_min_height', 800),
            ],
            'categoriesRequiringSize' => config('marketplace.categories_requiring_size', []),
            'categoryBrands' => collect(config('marketplace.category_brands', []))
                ->map(fn (array $brands) => collect($brands)
                    ->map(fn (string $brand) => ['value' => $brand, 'label' => $brand])
                    ->values()
                    ->all())
                ->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function resolveImageUrls(Request $request, ?Product $product = null): array
    {
        $imageService = app(ProductImageService::class);
        $existing = collect($request->input('existing_images', []))
            ->map(fn ($url) => PublicStorageUrl::toStoredPath(is_string($url) ? $url : null))
            ->filter()
            ->values()
            ->all();
        $uploads = $request->file('images', []);
        $newUrls = $imageService->storeUploads($request->user(), is_array($uploads) ? $uploads : []);

        if ($product !== null) {
            $previous = $product->image_urls ?? [];
            $removed = array_values(array_diff($previous, $existing));
            $imageService->deleteRemovedImages($product, $removed);
        }

        return array_values(array_merge($existing, $newUrls));
    }

    private function priceToCents(float|string $price): int
    {
        return (int) round(((float) $price) * 100);
    }

    private function compareAtPriceToCents(float|string|null $price): ?int
    {
        if ($price === null || $price === '') {
            return null;
        }

        return $this->priceToCents($price);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveClothingSize(array $validated): ?string
    {
        if (! in_array($validated['category'] ?? null, config('marketplace.categories_requiring_size', []), true)) {
            return null;
        }

        return $validated['clothing_size'] ?? null;
    }

    private function generateSku(int $userId): string
    {
        do {
            $sku = 'MM-'.$userId.'-'.strtoupper(Str::random(6));
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
