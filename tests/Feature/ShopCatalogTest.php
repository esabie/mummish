<?php

namespace Tests\Feature;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_lists_active_products_from_vendors_with_open_applications(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Pending);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Rainbow Stacker',
            'sku' => 'STACK-01',
            'category' => 'toys_development',
            'price_cents' => 2900,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A colorful stacker toy for toddlers learning balance and coordination.',
            'material_tags' => ['handmade'],
            'image_url' => 'https://example.com/stacker.jpg',
            'image_urls' => [
                'https://example.com/stacker.jpg',
                'https://example.com/stacker-2.jpg',
                'https://example.com/stacker-3.jpg',
            ],
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Draft Toy',
            'sku' => 'DRAFT-01',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Draft,
            'description' => 'Draft product description for testing shop visibility rules.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/draft-1.jpg',
                'https://example.com/draft-2.jpg',
                'https://example.com/draft-3.jpg',
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Index')
                ->where('result_count', 1)
                ->has('items', 1)
                ->where('items.0.id', $product->id)
                ->where('items.0.name', 'Rainbow Stacker')
                ->where('items.0.maker', 'Little Knot')
                ->where('items.0.vendor_user_id', $vendor->id)
                ->where('items.0.shop_slug', null)
                ->where('items.0.price', 'GHS 29.00')
                ->where('items.0.stock_quantity', 5)
            );
    }

    public function test_shop_lists_sold_out_products_with_flag(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Sold Out Toy',
            'sku' => 'SOLD-01',
            'category' => 'toys_development',
            'price_cents' => 1500,
            'stock_quantity' => 0,
            'status' => ProductStatus::Active,
            'description' => 'A popular toy that is temporarily unavailable.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/sold-1.jpg',
                'https://example.com/sold-2.jpg',
                'https://example.com/sold-3.jpg',
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $product->id)
                ->where('items.0.sold_out', true)
                ->where('items.0.stock_quantity', 0)
            );

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.sold_out', true)
                ->where('product.stock_quantity', 0)
            );
    }

    public function test_shop_hides_products_sold_out_beyond_grace_period(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Long Gone Toy',
            'sku' => 'GONE-01',
            'category' => 'toys_development',
            'price_cents' => 1500,
            'stock_quantity' => 0,
            'status' => ProductStatus::Active,
            'description' => 'A toy that sold out a long time ago.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/gone-1.jpg',
                'https://example.com/gone-2.jpg',
                'https://example.com/gone-3.jpg',
            ],
        ]);

        $product->forceFill(['sold_out_at' => now()->subDays(11)])->saveQuietly();

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 0)
                ->has('items', 0)
            );

        $this->get(route('shop.show', $product))->assertNotFound();

        // Restocking clears sold_out_at and brings the product back.
        $product->refresh();
        $product->update(['stock_quantity' => 3]);

        $this->assertNull($product->fresh()->sold_out_at);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $product->id)
            );
    }

    public function test_cart_stock_endpoint_returns_available_quantities(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Denim Joggers',
            'sku' => 'JOG-01',
            'category' => 'clothing_footwear',
            'brand' => 'Carter\'s',
            'price_cents' => 10000,
            'stock_quantity' => 7,
            'status' => ProductStatus::Active,
            'description' => 'Comfortable denim joggers for active toddlers.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/joggers-1.jpg',
                'https://example.com/joggers-2.jpg',
                'https://example.com/joggers-3.jpg',
            ],
        ]);

        $this->postJson(route('shop.cart-stock'), [
            'product_ids' => [$product->id, 99999],
        ])
            ->assertOk()
            ->assertJsonPath('stocks.'.$product->id, 7);
    }

    public function test_shop_hides_products_from_rejected_vendors(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Rejected);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Hidden Toy',
            'sku' => 'HIDE-01',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'Hidden product description for rejected vendor shop test.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/hide-1.jpg',
                'https://example.com/hide-2.jpg',
                'https://example.com/hide-3.jpg',
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 0)
                ->has('items', 0)
            );
    }

    public function test_shop_catalog_includes_store_slug_for_approved_vendors(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved, 'little-knot');

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Approved Product',
            'sku' => 'APP-01',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
            'description' => 'Product from an approved vendor.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/app-1.jpg',
                'https://example.com/app-2.jpg',
                'https://example.com/app-3.jpg',
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('items.0.shop_slug', 'little-knot')
                ->where('items.0.vendor_user_id', $vendor->id)
            );
    }

    public function test_shop_show_displays_product_detail(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved, 'little-knot');

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => 'WOOD-01',
            'category' => 'toys_development',
            'brand' => 'Fisher-Price',
            'price_cents' => 4500,
            'stock_quantity' => 12,
            'status' => ProductStatus::Active,
            'description' => 'Classic wooden blocks for building towers and imaginative play.',
            'material_tags' => ['handmade', 'organic_cotton'],
            'allows_customization' => true,
            'image_url' => 'https://example.com/blocks.jpg',
            'image_urls' => [
                'https://example.com/blocks.jpg',
                'https://example.com/blocks-2.jpg',
                'https://example.com/blocks-3.jpg',
            ],
        ]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Show')
                ->where('product.id', $product->id)
                ->where('product.name', 'Wooden Blocks')
                ->where('product.seller.name', 'Little Knot')
                ->where('product.seller.slug', 'little-knot')
                ->where('product.vendor_user_id', $vendor->id)
                ->where('product.gallery', fn ($gallery) => count($gallery) >= 3)
                ->where('product.stock_quantity', 12)
                ->where('product.brand', 'Fisher-Price')
                ->where('product.sku', 'WOOD-01')
                ->where('product.material_tags', ['Handmade', 'Organic Cotton'])
                ->where('product.allows_customization', true)
                ->has('product.tags', 2)
            );
    }

    public function test_shop_show_returns_not_found_for_draft_product(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Pending);

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Secret Draft',
            'sku' => 'DRAFT-02',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Draft,
            'description' => 'Secret draft product that should not appear on the shop.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/secret-1.jpg',
                'https://example.com/secret-2.jpg',
                'https://example.com/secret-3.jpg',
            ],
        ]);

        $this->get(route('shop.show', $product))->assertNotFound();
    }

    public function test_shop_filters_by_category(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $toy = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Rainbow Stacker',
            'sku' => 'STACK-01',
            'category' => 'toys_development',
            'price_cents' => 2900,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A colorful stacker toy.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/stacker.jpg',
                'https://example.com/stacker-2.jpg',
                'https://example.com/stacker-3.jpg',
            ],
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Baby Onesie',
            'sku' => 'ONESIE-01',
            'category' => 'clothing_footwear',
            'price_cents' => 4500,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
            'description' => 'Soft cotton onesie.',
            'material_tags' => ['organic_cotton'],
            'image_urls' => [
                'https://example.com/onesie.jpg',
                'https://example.com/onesie-2.jpg',
                'https://example.com/onesie-3.jpg',
            ],
        ]);

        $this->get(route('shop.index', ['category' => 'toys_development']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $toy->id)
                ->where('applied_filters.category', 'toys_development')
                ->has('filter_options.categories', 2)
            );
    }

    public function test_shop_filters_eco_friendly_products(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $ecoProduct = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Organic Bib',
            'sku' => 'BIB-01',
            'category' => 'feeding_nursing',
            'price_cents' => 1500,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
            'description' => 'Organic cotton bib.',
            'material_tags' => ['organic_cotton'],
            'image_urls' => [
                'https://example.com/bib.jpg',
                'https://example.com/bib-2.jpg',
                'https://example.com/bib-3.jpg',
            ],
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Plastic Rattle',
            'sku' => 'RATTLE-01',
            'category' => 'toys_development',
            'price_cents' => 1200,
            'stock_quantity' => 8,
            'status' => ProductStatus::Active,
            'description' => 'Classic plastic rattle.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/rattle.jpg',
                'https://example.com/rattle-2.jpg',
                'https://example.com/rattle-3.jpg',
            ],
        ]);

        $this->get(route('shop.index', ['eco' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $ecoProduct->id)
                ->where('applied_filters.eco', true)
            );
    }

    public function test_shop_filters_by_condition(): void
    {
        $vendor = $this->createVendor(VendorApplicationStatus::Approved);

        $newProduct = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Brand New Blocks',
            'sku' => 'NEW-01',
            'category' => 'toys_development',
            'condition' => ProductCondition::New,
            'price_cents' => 2900,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'Brand new wooden blocks.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/new-1.jpg',
                'https://example.com/new-2.jpg',
                'https://example.com/new-3.jpg',
            ],
        ]);

        $usedProduct = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Gently Used Jumper',
            'sku' => 'USED-01',
            'category' => 'clothing_footwear',
            'condition' => ProductCondition::FairlyUsed,
            'price_cents' => 1500,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
            'description' => 'Gently used jumper.',
            'material_tags' => ['organic_cotton'],
            'image_urls' => [
                'https://example.com/used-1.jpg',
                'https://example.com/used-2.jpg',
                'https://example.com/used-3.jpg',
            ],
        ]);

        $this->get(route('shop.index', ['condition' => 'new']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $newProduct->id)
                ->where('applied_filters.condition', 'new')
            );

        $this->get(route('shop.index', ['condition' => 'pre_loved']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('result_count', 1)
                ->where('items.0.id', $usedProduct->id)
                ->where('applied_filters.condition', 'pre_loved')
            );
    }

    private function createVendor(VendorApplicationStatus $status, ?string $shopSlug = null): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'shop_slug' => $status === VendorApplicationStatus::Approved
                ? ($shopSlug ?? 'little-knot')
                : null,
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => $status,
        ]);

        return $user;
    }
}
