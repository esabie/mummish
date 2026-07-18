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

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_with_live_catalog_sections(): void
    {
        $vendor = $this->createApprovedVendor('Oak & Acorn', 'oak-and-acorn');

        Product::create([
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

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('categories', count(config('marketplace.categories')))
                ->where('categories.0.id', 'feeding_nursing')
                ->where('categories.7.id', 'toys_development')
                ->where('categories.0.count', 1)
                ->where('categories.7.count', 1)
                ->has('featured_stores', 1)
                ->where('featured_stores.0.slug', 'oak-and-acorn')
                ->where('featured_stores.0.name', 'Oak & Acorn')
                ->where('featured_stores.0.product_count', 2)
                ->has('popular_products', 2)
                ->where('popular_products.0.name', 'Organic Bib')
            );
    }

    public function test_homepage_excludes_unapproved_stores(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Pending Shop',
            'shop_slug' => null,
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        Product::create([
            'user_id' => $vendor->id,
            'title' => 'Pending Toy',
            'sku' => 'PEND-01',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'Toy from pending vendor.',
            'material_tags' => ['handmade'],
            'image_urls' => [
                'https://example.com/pending.jpg',
                'https://example.com/pending-2.jpg',
                'https://example.com/pending-3.jpg',
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('featured_stores', 0)
                ->has('categories', count(config('marketplace.categories')))
                ->where('categories.7.count', 1)
                ->has('popular_products', 1)
            );
    }

    private function createApprovedVendor(string $shopName, string $shopSlug): User
    {
        $user = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => $shopName,
            'shop_slug' => $shopSlug,
            'business_email' => $user->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);

        return $user;
    }
}
