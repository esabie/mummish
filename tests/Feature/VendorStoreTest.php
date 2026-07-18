<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VendorStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_vendor_storefront_lists_only_their_products(): void
    {
        $vendor = $this->createApprovedVendor('Little Knot', 'little-knot');
        $otherVendor = $this->createApprovedVendor('Other Shop', 'other-shop');

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Rainbow Stacker',
            'sku' => 'STACK-01',
            'category' => 'toys_development',
            'price_cents' => 2900,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A colorful stacker toy for toddlers.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/stacker.jpg',
                'https://example.com/stacker-2.jpg',
                'https://example.com/stacker-3.jpg',
            ],
        ]);

        Product::create([
            'user_id' => $otherVendor->id,
            'title' => 'Other Product',
            'sku' => 'OTHER-01',
            'category' => 'toys_development',
            'price_cents' => 1500,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
            'description' => 'Product from another vendor.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/other-1.jpg',
                'https://example.com/other-2.jpg',
                'https://example.com/other-3.jpg',
            ],
        ]);

        $this->get(route('shops.show', 'little-knot'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/VendorStore')
                ->where('store.slug', 'little-knot')
                ->where('store.name', 'Little Knot')
                ->where('store.url', url()->route('shops.show', 'little-knot'))
                ->where('result_count', 1)
                ->has('items', 1)
                ->where('items.0.id', $product->id)
            );
    }

    public function test_vendor_store_product_page_scoped_to_store(): void
    {
        $vendor = $this->createApprovedVendor('Little Knot', 'little-knot');

        $product = Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => 'WOOD-01',
            'category' => 'toys_development',
            'price_cents' => 4500,
            'stock_quantity' => 12,
            'status' => ProductStatus::Active,
            'description' => 'Classic wooden blocks.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/blocks.jpg',
                'https://example.com/blocks-2.jpg',
                'https://example.com/blocks-3.jpg',
            ],
        ]);

        $this->get(route('shops.products.show', ['slug' => 'little-knot', 'id' => $product->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Show')
                ->where('product.id', $product->id)
                ->where('store.slug', 'little-knot')
                ->where('store.name', 'Little Knot')
            );
    }

    public function test_unknown_store_slug_returns_not_found(): void
    {
        $this->get(route('shops.show', 'missing-store'))->assertNotFound();
    }

    public function test_stores_directory_lists_only_approved_vendors(): void
    {
        $approved = $this->createApprovedVendor('Little Knot', 'little-knot');

        $pendingVendor = User::factory()->create(['role' => UserRole::Vendor]);
        VendorApplication::create([
            'user_id' => $pendingVendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Pending Shop',
            'business_email' => $pendingVendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        Product::create([
            'user_id' => $approved->id,
            'title' => 'Rainbow Stacker',
            'sku' => 'STACK-01',
            'category' => 'toys_development',
            'price_cents' => 2900,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'description' => 'A colorful stacker toy for toddlers.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/stacker.jpg',
                'https://example.com/stacker-2.jpg',
                'https://example.com/stacker-3.jpg',
            ],
        ]);

        $this->get(route('shops.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/StoresDirectory')
                ->where('result_count', 1)
                ->has('stores', 1)
                ->where('stores.0.slug', 'little-knot')
                ->where('stores.0.name', 'Little Knot')
                ->where('stores.0.product_count', 1)
            );
    }

    public function test_stores_directory_filters_by_search_query(): void
    {
        $this->createApprovedVendor('Little Knot', 'little-knot');
        $this->createApprovedVendor('Tiny Threads', 'tiny-threads');

        $this->get(route('shops.index', ['q' => 'tiny']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/StoresDirectory')
                ->where('search_query', 'tiny')
                ->where('result_count', 1)
                ->has('stores', 1)
                ->where('stores.0.slug', 'tiny-threads')
            );
    }

    public function test_pending_vendor_without_slug_has_no_storefront(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Pending Shop',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $this->get(route('shops.show', 'pending-shop'))->assertNotFound();
    }

    private function createApprovedVendor(string $shopName, string $slug): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => $shopName,
            'shop_slug' => $slug,
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return $user;
    }
}
