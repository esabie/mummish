<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_products_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk();
    }

    public function test_admin_can_view_product_details(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = $this->createProduct(ProductStatus::Active);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}")
            ->assertOk();
    }

    public function test_non_admin_cannot_access_products(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/products')
            ->assertForbidden();
    }

    private function createProduct(ProductStatus $status): Product
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        VendorApplication::create([
            'user_id' => $vendor->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $vendor->email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        return Product::create([
            'user_id' => $vendor->id,
            'title' => 'Wooden Blocks',
            'sku' => 'LH-001',
            'category' => 'toys_development',
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'status' => $status,
        ]);
    }
}
