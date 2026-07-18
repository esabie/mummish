<?php

namespace App\Http\Controllers;

use App\Enums\ProductCondition;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\Product;
use App\Models\User;
use App\Support\AppLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $category = $this->validCategory($request->query('category'));
        $priceMaxGhs = max(0, (int) $request->query('price_max', 0));
        $ecoOnly = $request->boolean('eco');
        $makerId = max(0, (int) $request->query('maker', 0));
        $condition = $this->validCondition($request->query('condition'));
        $sort = $this->validSort((string) $request->query('sort', 'newest'));
        $perPage = max(1, min((int) config('marketplace.shop_per_page', 12), 48));

        AppLog::info('[Shop] Catalog index requested.', [
            'search_query' => $query !== '' ? $query : null,
            'category' => $category,
            'price_max_ghs' => $priceMaxGhs > 0 ? $priceMaxGhs : null,
            'eco_only' => $ecoOnly,
            'maker_id' => $makerId > 0 ? $makerId : null,
            'condition' => $condition,
            'sort' => $sort,
            'per_page' => $perPage,
        ]);

        $filterOptions = $this->shopFilterOptions();
        $priceCeiling = $filterOptions['price_ceiling'];
        $priceMaxCents = $priceMaxGhs > 0 && $priceMaxGhs < $priceCeiling
            ? $priceMaxGhs * 100
            : null;

        $paginator = Product::query()
            ->visibleInShop()
            ->with(['user.vendorApplication'])
            ->search($query !== '' ? $query : null)
            ->inCategory($category)
            ->maxPriceCents($priceMaxCents)
            ->ecoFriendly($ecoOnly)
            ->forMaker($makerId > 0 ? $makerId : null)
            ->shopCondition($condition)
            ->shopSort($sort, $query !== '')
            ->paginate($perPage)
            ->withQueryString();

        AppLog::debug('[Shop] Catalog index results.', [
            'result_count' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'item_ids' => $paginator->getCollection()->pluck('id')->all(),
        ]);

        return Inertia::render('Shop/Index', [
            'search_query' => $query,
            'result_count' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => max(1, $paginator->lastPage()),
            'items' => $paginator
                ->getCollection()
                ->map(fn (Product $product) => $product->toShopCatalogItem())
                ->values()
                ->all(),
            'filter_options' => $filterOptions,
            'applied_filters' => [
                'category' => $category,
                'price_max' => $priceMaxGhs > 0 && $priceMaxGhs < $priceCeiling ? $priceMaxGhs : null,
                'eco' => $ecoOnly,
                'maker' => $makerId > 0 ? $makerId : null,
                'condition' => $condition,
                'sort' => $sort,
            ],
        ]);
    }

    public function cartStock(Request $request): JsonResponse
    {
        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return response()->json(['stocks' => []]);
        }

        $stocks = Product::query()
            ->visibleInShop()
            ->whereIn('id', $productIds)
            ->pluck('stock_quantity', 'id')
            ->map(fn ($qty) => (int) $qty);

        AppLog::debug('[Shop] Cart stock lookup.', [
            'requested_product_ids' => $productIds->all(),
            'resolved_stock_count' => $stocks->count(),
        ]);

        return response()->json(['stocks' => $stocks]);
    }

    public function show(int $id): Response
    {
        AppLog::info('[Shop] Product detail requested.', ['product_id' => $id]);

        $product = Product::query()
            ->visibleInShop()
            ->with(['user.vendorApplication'])
            ->find($id);

        if ($product === null) {
            AppLog::warning('[Shop] Product not found.', ['product_id' => $id]);

            abort(404);
        }

        AppLog::debug('[Shop] Product detail resolved.', [
            'product_id' => $product->id,
            'vendor_user_id' => $product->user_id,
            'status' => $product->status->value,
            'stock_quantity' => $product->stock_quantity,
        ]);

        $catalogItem = $product->toShopCatalogItem();

        return Inertia::render('Shop/Show', [
            'product' => $this->formatProductForPage($catalogItem),
        ]);
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public function formatProductForPage(array $product): array
    {
        $materialTags = $product['material_tags'] ?? [];
        $tagTones = ['mint', 'sky', 'peach'];

        $defaults = [
            'category' => 'Toys',
            'compare_at_price' => null,
            'seller' => [
                'name' => $product['maker'] ?? 'Mummish Seller',
                'initial' => strtoupper(substr((string) ($product['maker'] ?? 'L'), 0, 1)),
                'approved' => ($product['shop_slug'] ?? null) !== null,
                'slug' => $product['shop_slug'] ?? null,
                'user_id' => $product['vendor_user_id'] ?? null,
            ],
            'tags' => collect($materialTags)
                ->values()
                ->map(fn (string $label, int $index) => [
                    'label' => $label,
                    'tone' => $tagTones[$index % count($tagTones)],
                ])
                ->all(),
            'material_care' => $materialTags !== []
                ? 'Materials & sustainability: '.implode(', ', $materialTags).'.'
                : 'Material details provided by the seller.',
            'shipping_returns' => 'Shipping cost is calculated at checkout based on your delivery area. Delivery times vary by seller and location. Contact the seller after ordering if you have questions about returns.',
            'shipping_note' => 'Shipping is calculated at checkout based on your delivery area.',
            'gallery' => null,
            'reviews' => [],
        ];

        $merged = array_merge($defaults, $product);

        if (! isset($merged['gallery']) || $merged['gallery'] === null || $merged['gallery'] === []) {
            $merged['gallery'] = array_filter([$merged['image'] ?? null]);
        }

        return $merged;
    }

    /**
     * @return array{
     *     categories: array<int, array{id: string, label: string, count: int}>,
     *     conditions: array<int, array{id: string, label: string, count: int}>,
     *     makers: array<int, array{id: int, name: string}>,
     *     price_ceiling: int
     * }
     */
    private function shopFilterOptions(): array
    {
        $visible = Product::query()->visibleInShop();

        $categoryCounts = (clone $visible)
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $categories = collect(StoreVendorApplicationRequest::categories())
            ->only($categoryCounts->keys())
            ->map(fn (string $label, string $key) => [
                'id' => $key,
                'label' => $label,
                'count' => (int) $categoryCounts[$key],
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $conditionCounts = (clone $visible)
            ->selectRaw('`condition`, COUNT(*) as aggregate')
            ->groupBy('condition')
            ->pluck('aggregate', 'condition');

        $preLovedCount = (int) ($conditionCounts[ProductCondition::FairlyUsed->value] ?? 0)
            + (int) ($conditionCounts[ProductCondition::Used->value] ?? 0);

        $conditions = collect(ProductCondition::cases())
            ->map(fn (ProductCondition $productCondition) => [
                'id' => $productCondition->value,
                'label' => $productCondition->label(),
                'count' => (int) ($conditionCounts[$productCondition->value] ?? 0),
            ])
            ->filter(fn (array $option) => $option['count'] > 0)
            ->values()
            ->all();

        if ($preLovedCount > 0) {
            $conditions[] = [
                'id' => 'pre_loved',
                'label' => 'Used & pre-loved',
                'count' => $preLovedCount,
            ];
        }

        $makerIds = (clone $visible)
            ->distinct()
            ->pluck('user_id');

        $makers = User::query()
            ->whereIn('id', $makerIds)
            ->with(['vendorApplication' => fn ($q) => $q->select('vendor_applications.id', 'vendor_applications.user_id', 'vendor_applications.shop_name')])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->vendorApplication?->shop_name ?? 'Mummish Seller',
            ])
            ->sortBy('name')
            ->values()
            ->all();

        $maxPriceCents = (int) ((clone $visible)->max('price_cents') ?? 0);
        $priceCeiling = max(200, (int) ceil($maxPriceCents / 100));

        return [
            'categories' => $categories,
            'conditions' => $conditions,
            'makers' => $makers,
            'price_ceiling' => $priceCeiling,
        ];
    }

    private function validCondition(mixed $condition): ?string
    {
        if (! is_string($condition) || $condition === '') {
            return null;
        }

        if ($condition === 'pre_loved') {
            return 'pre_loved';
        }

        return ProductCondition::tryFrom($condition)?->value;
    }

    private function validCategory(mixed $category): ?string
    {
        if (! is_string($category) || $category === '') {
            return null;
        }

        return array_key_exists($category, StoreVendorApplicationRequest::categories())
            ? $category
            : null;
    }

    private function validSort(string $sort): string
    {
        return in_array($sort, ['relevance', 'price_low', 'price_high', 'newest'], true)
            ? $sort
            : 'newest';
    }
}
