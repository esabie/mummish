<?php

namespace App\Models;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'sku',
        'category',
        'brand',
        'condition',
        'clothing_size',
        'price_cents',
        'compare_at_price_cents',
        'stock_quantity',
        'image_url',
        'image_urls',
        'material_tags',
        'allows_customization',
        'status',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'compare_at_price_cents' => 'integer',
        'stock_quantity' => 'integer',
        'sold_out_at' => 'datetime',
        'material_tags' => 'array',
        'image_urls' => 'array',
        'allows_customization' => 'boolean',
        'status' => ProductStatus::class,
        'condition' => ProductCondition::class,
    ];

    protected static function booted(): void
    {
        // Keep sold_out_at in sync with stock so the shop can hide products
        // that have been sold out for too long (see scopeVisibleInShop).
        static::saving(function (Product $product) {
            if ($product->stock_quantity > 0) {
                $product->sold_out_at = null;
            } elseif ($product->sold_out_at === null) {
                $product->sold_out_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForVendor(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Draft);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        $threshold = (int) config('marketplace.low_stock_threshold', 5);

        return $query
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold);
    }

    public function scopeVisibleInShop(Builder $query): Builder
    {
        $hiddenAfterDays = (int) config('marketplace.sold_out_hidden_after_days', 10);

        return $query
            ->active()
            // Sold-out products stay visible for a grace period, then drop off
            // the shop until the vendor restocks (which clears sold_out_at).
            ->where(function (Builder $stockQuery) use ($hiddenAfterDays) {
                $stockQuery
                    ->where('stock_quantity', '>', 0)
                    ->orWhereNull('sold_out_at')
                    ->orWhere('sold_out_at', '>', now()->subDays($hiddenAfterDays));
            })
            ->whereHas('user.vendorApplication', function (Builder $applicationQuery) {
                $applicationQuery->whereIn('status', [
                    VendorApplicationStatus::Pending,
                    VendorApplicationStatus::Approved,
                ]);
            });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('category', 'like', $term);
        });
    }

    public function scopeInCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || $category === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeMaxPriceCents(Builder $query, ?int $maxCents): Builder
    {
        if ($maxCents === null || $maxCents <= 0) {
            return $query;
        }

        return $query->where('price_cents', '<=', $maxCents);
    }

    public function scopeEcoFriendly(Builder $query, bool $ecoOnly): Builder
    {
        if (! $ecoOnly) {
            return $query;
        }

        $ecoTags = ['organic_cotton', 'recycled_materials', 'plastic_free', 'fair_trade'];

        return $query->where(function (Builder $q) use ($ecoTags) {
            foreach ($ecoTags as $tag) {
                $q->orWhereJsonContains('material_tags', $tag);
            }
        });
    }

    public function scopeForMaker(Builder $query, ?int $vendorUserId): Builder
    {
        if ($vendorUserId === null || $vendorUserId <= 0) {
            return $query;
        }

        return $query->where('user_id', $vendorUserId);
    }

    public function scopeShopCondition(Builder $query, ?string $condition): Builder
    {
        if ($condition === null || $condition === '') {
            return $query;
        }

        if ($condition === 'pre_loved') {
            return $query->whereIn('condition', [
                ProductCondition::FairlyUsed->value,
                ProductCondition::Used->value,
            ]);
        }

        $enum = ProductCondition::tryFrom($condition);

        if ($enum === null) {
            return $query;
        }

        return $query->where('condition', $enum->value);
    }

    public function scopeShopSort(Builder $query, string $sort, bool $hasSearch): Builder
    {
        return match ($sort) {
            'price_low' => $query->orderBy('price_cents')->orderByDesc('id'),
            'price_high' => $query->orderByDesc('price_cents')->orderByDesc('id'),
            'relevance' => $hasSearch
                ? $query->latest('id')
                : $query->latest('id'),
            default => $query->latest('id'),
        };
    }

    public function isLowStock(): bool
    {
        $threshold = (int) config('marketplace.low_stock_threshold', 5);

        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity === 0;
    }

    public function categoryLabel(): string
    {
        if ($this->category === null) {
            return 'Uncategorized';
        }

        return StoreVendorApplicationRequest::categories()[$this->category]
            ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function requiresClothingSize(): bool
    {
        return in_array($this->category, config('marketplace.categories_requiring_size', ['clothing']), true);
    }

    public function clothingSizeLabel(): ?string
    {
        if ($this->clothing_size === null) {
            return null;
        }

        return config('marketplace.clothing_sizes')[$this->clothing_size]
            ?? $this->clothing_size;
    }

    public function conditionLabel(): string
    {
        return $this->condition?->label() ?? ProductCondition::New->label();
    }

    public function formattedPrice(): string
    {
        return 'GHS '.number_format($this->price_cents / 100, 2);
    }

    public function formattedCompareAtPrice(): ?string
    {
        if ($this->compare_at_price_cents === null) {
            return null;
        }

        return 'GHS '.number_format($this->compare_at_price_cents / 100, 2);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price_cents !== null
            && $this->compare_at_price_cents > $this->price_cents;
    }

    public function discountPercent(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return (int) round((1 - ($this->price_cents / $this->compare_at_price_cents)) * 100);
    }

    public function stockLevelPercent(): int
    {
        $cap = max(20, (int) config('marketplace.stock_display_cap', 50));

        return (int) min(100, round(($this->stock_quantity / $cap) * 100));
    }

    /**
     * @return array<int, string>
     */
    public function shopImageUrls(): array
    {
        $urls = collect($this->image_urls ?? [])
            ->map(fn ($url) => PublicStorageUrl::fromStored(is_string($url) ? $url : null))
            ->filter()
            ->values()
            ->all();

        if ($urls !== []) {
            return $urls;
        }

        $legacy = PublicStorageUrl::fromStored($this->image_url);

        if ($legacy !== null) {
            return [$legacy];
        }

        return [(string) config('marketplace.product_placeholder_image')];
    }

    public function shopImageUrl(): string
    {
        return $this->shopImageUrls()[0];
    }

    /**
     * @return array<int, string>
     */
    public function materialTagLabels(): array
    {
        $options = config('marketplace.product_material_tags', []);

        return collect($this->material_tags ?? [])
            ->map(function (string $tag) use ($options) {
                if (str_starts_with($tag, 'custom:')) {
                    return ucwords(str_replace('_', ' ', substr($tag, 7)));
                }

                return $options[$tag] ?? ucwords(str_replace('_', ' ', $tag));
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{text: string, variant: string}>
     */
    public function shopBadges(): array
    {
        $badges = [];

        if ($this->isOnSale()) {
            $percent = $this->discountPercent();
            $badges[] = [
                'text' => $percent !== null ? "{$percent}% OFF" : 'SALE',
                'variant' => 'sale',
            ];
        }

        if ($this->created_at !== null && $this->created_at->isAfter(now()->subDays(14))) {
            $badges[] = ['text' => 'NEW', 'variant' => 'new'];
        }

        if ($this->isLowStock()) {
            $badges[] = ['text' => 'LOW STOCK', 'variant' => 'accent'];
        }

        return $badges;
    }

    /**
     * @return array<string, mixed>
     */
    public function toShopCatalogItem(): array
    {
        $this->loadMissing('user.vendorApplication');

        $shop = $this->user->vendorApplication;
        $maker = $shop?->shop_name ?? 'Mummish Seller';
        $shopSlug = ($shop?->isApproved() && $shop?->shop_slug)
            ? $shop->shop_slug
            : null;

        return [
            'id' => $this->id,
            'vendor_user_id' => $this->user_id,
            'shop_slug' => $shopSlug,
            'maker' => $maker,
            'name' => $this->title,
            'sku' => $this->sku,
            'category' => $this->categoryLabel(),
            'brand' => $this->brand,
            'condition' => $this->condition?->value ?? ProductCondition::New->value,
            'condition_label' => $this->conditionLabel(),
            'material_tags' => $this->materialTagLabels(),
            'allows_customization' => $this->allows_customization,
            'size' => $this->clothingSizeLabel(),
            'size_key' => $this->clothing_size,
            'requires_size' => $this->requiresClothingSize(),
            'size_options' => $this->requiresClothingSize()
                ? collect(config('marketplace.clothing_sizes', []))
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all()
                : [],
            'price' => $this->formattedPrice(),
            'price_cents' => $this->price_cents,
            'compare_at_price' => $this->formattedCompareAtPrice(),
            'discount_percent' => $this->discountPercent(),
            'on_sale' => $this->isOnSale(),
            'stock_quantity' => $this->stock_quantity,
            'sold_out' => $this->isOutOfStock(),
            'image' => $this->shopImageUrl(),
            'gallery' => $this->shopImageUrls(),
            'badges' => $this->shopBadges(),
            'description' => $this->description !== null && trim(strip_tags($this->description)) !== ''
                ? strip_tags($this->description)
                : sprintf(
                    '%s from %s — thoughtfully made for little ones.',
                    $this->title,
                    $maker
                ),
        ];
    }
}
