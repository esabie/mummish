<?php

namespace Tests\Feature\Vendor;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TestProductImage;
use Tests\TestCase;

class VendorProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_create_product_rejects_method_put_spoof(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            '_method' => 'put',
        ]));

        $response->assertStatus(405);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_vendor_can_create_product(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload());

        $response->assertRedirect(route('vendor.inventory.index'));
        $response->assertSessionHas('success');

        $product = Product::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($product);
        $this->assertSame('Wooden Blocks', $product->title);
        $this->assertStringStartsWith('LH-'.$user->id.'-', $product->sku);
        $this->assertSame(2450, $product->price_cents);
        $this->assertSame(10, $product->stock_quantity);
        $this->assertSame(ProductCondition::New, $product->condition);
        $this->assertCount(3, $product->image_urls ?? []);
    }

    public function test_clothing_product_requires_size(): void
    {
        $user = $this->vendorWithApplication();

        $payload = $this->validProductPayload([
            'category' => 'clothing_footwear',
            'brand' => "Carter's",
        ]);

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $payload);

        $response->assertSessionHasErrors('clothing_size');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_vendor_can_create_clothing_product_with_size(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'category' => 'clothing_footwear',
            'brand' => "Carter's",
            'clothing_size' => '3_6m',
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $product = Product::query()->where('user_id', $user->id)->first();

        $this->assertSame('clothing_footwear', $product->category);
        $this->assertSame('3_6m', $product->clothing_size);
    }

    public function test_non_clothing_product_clears_clothing_size(): void
    {
        $user = $this->vendorWithApplication();

        $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'category' => 'toys_development',
            'clothing_size' => '3_6m',
        ]));

        $product = Product::query()->where('user_id', $user->id)->first();

        $this->assertSame('toys_development', $product->category);
        $this->assertNull($product->clothing_size);
    }

    public function test_create_product_rejects_low_resolution_images(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = $this->vendorWithApplication();

        $payload = $this->validProductPayload([
            'images' => [
                TestProductImage::sharpJpeg('one.jpg', 400, 400),
                TestProductImage::sharpJpeg('two.jpg', 400, 400),
                TestProductImage::sharpJpeg('three.jpg', 400, 400),
            ],
        ]);

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $payload);

        $response->assertSessionHasErrors('images.0');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_create_product_requires_three_images(): void
    {
        $user = $this->vendorWithApplication();

        $payload = $this->validProductPayload();
        $payload['images'] = [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
        ];

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $payload);

        $response->assertSessionHasErrors('images');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_vendor_can_create_product_on_sale(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'compare_at_price' => '30.00',
            'price' => '24.00',
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $product = Product::query()->where('user_id', $user->id)->first();

        $this->assertSame(3000, $product->compare_at_price_cents);
        $this->assertSame(2400, $product->price_cents);
        $this->assertTrue($product->isOnSale());
        $this->assertSame(20, $product->discountPercent());
    }

    public function test_create_product_rejects_original_price_not_above_sale_price(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'compare_at_price' => '20.00',
            'price' => '24.00',
        ]));

        $response->assertSessionHasErrors('compare_at_price');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_vendor_can_create_product_with_used_condition(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'condition' => ProductCondition::FairlyUsed->value,
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $product = Product::query()->where('user_id', $user->id)->first();

        $this->assertSame(ProductCondition::FairlyUsed, $product->condition);
    }

    public function test_create_product_requires_all_fields(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), [
            'title' => '',
            'status' => ProductStatus::Draft->value,
        ]);

        $response->assertSessionHasErrors([
            'title',
            'category',
            'brand',
            'condition',
            'price',
            'stock_quantity',
            'description',
            'material_tags',
            'allows_customization',
            'images',
        ]);
    }

    public function test_create_product_rejects_invalid_brand_for_category(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'brand' => 'Pampers',
        ]));

        $response->assertSessionHasErrors('brand');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_create_product_rejects_numeric_description(): void
    {
        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'description' => 'This product has 12 colorful pieces for everyday play.',
        ]));

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_pending_vendor_cannot_exceed_listing_limit(): void
    {
        config(['marketplace.max_listings_while_pending' => 2]);

        $user = $this->vendorWithApplication();

        Product::create([
            'user_id' => $user->id,
            'title' => 'One',
            'sku' => 'ONE',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
        ]);

        Product::create([
            'user_id' => $user->id,
            'title' => 'Two',
            'sku' => 'TWO',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'Another wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
        ]);

        $response = $this->actingAs($user)->post(route('vendor.inventory.store'), $this->validProductPayload([
            'title' => 'Three',
        ]));

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('products', 2);
    }

    public function test_vendor_cannot_create_when_at_limit_redirects_from_create_page(): void
    {
        config(['marketplace.max_listings_while_pending' => 1]);

        $user = $this->vendorWithApplication();

        Product::create([
            'user_id' => $user->id,
            'title' => 'Only one',
            'sku' => 'ONLY',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
        ]);

        $this->actingAs($user)
            ->get(route('vendor.inventory.create'))
            ->assertRedirect(route('vendor.inventory.index'))
            ->assertSessionHas('error');
    }

    public function test_vendor_can_update_own_product(): void
    {
        $user = $this->vendorWithApplication();

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Old name',
            'sku' => 'OLD',
            'category' => 'toys_development',
            'brand' => 'Fisher-Price',
            'condition' => ProductCondition::New,
            'price_cents' => 1000,
            'stock_quantity' => 5,
            'status' => ProductStatus::Draft,
            'description' => 'Old description that is long enough for validation rules.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
            'image_url' => $this->sampleImageUrls()[0],
        ]);

        $response = $this->actingAs($user)->put(route('vendor.inventory.update', $product), $this->validProductPayload([
            'title' => 'New name',
            'category' => 'nutrition',
            'brand' => 'Nan',
            'price' => '19.99',
            'stock_quantity' => 8,
            'status' => ProductStatus::Active->value,
            'existing_images' => $this->sampleImageUrls(),
            'images' => [],
        ]));

        $response->assertRedirect(route('vendor.inventory.index'));

        $product->refresh();
        $this->assertSame('New name', $product->title);
        $this->assertSame('OLD', $product->sku);
        $this->assertSame(1999, $product->price_cents);
        $this->assertSame(ProductStatus::Active, $product->status);
    }

    public function test_vendor_cannot_edit_another_vendors_product(): void
    {
        $owner = $this->vendorWithApplication();
        $other = $this->vendorWithApplication('other@example.com');

        $product = Product::create([
            'user_id' => $owner->id,
            'title' => 'Owned',
            'sku' => 'OWN',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
        ]);

        $this->actingAs($other)->get(route('vendor.inventory.edit', $product))->assertNotFound();
    }

    public function test_vendor_can_delete_own_product(): void
    {
        $user = $this->vendorWithApplication();

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'To delete',
            'sku' => 'DEL',
            'category' => 'toys_development',
            'price_cents' => 1000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Active,
            'description' => 'A wonderful product for kids to enjoy every day.',
            'material_tags' => ['handmade'],
            'image_urls' => $this->sampleImageUrls(),
        ]);

        $this->actingAs($user)
            ->delete(route('vendor.inventory.destroy', $product))
            ->assertRedirect(route('vendor.inventory.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProductPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Wooden Blocks',
            'category' => 'toys_development',
            'brand' => 'Fisher-Price',
            'condition' => ProductCondition::New->value,
            'description' => 'Handmade wooden blocks for creative open-ended play every day.',
            'material_tags' => ['handmade'],
            'price' => '24.50',
            'stock_quantity' => 10,
            'status' => ProductStatus::Active->value,
            'allows_customization' => false,
            'images' => extension_loaded('gd')
                ? [
                    TestProductImage::sharpJpeg('one.jpg'),
                    TestProductImage::sharpJpeg('two.jpg'),
                    TestProductImage::sharpJpeg('three.jpg'),
                ]
                : [
                    UploadedFile::fake()->image('one.jpg', 1200, 1200),
                    UploadedFile::fake()->image('two.jpg', 1200, 1200),
                    UploadedFile::fake()->image('three.jpg', 1200, 1200),
                ],
        ], $overrides);
    }

    /**
     * @return array<int, string>
     */
    private function sampleImageUrls(): array
    {
        $urls = [];

        foreach (['a.jpg', 'b.jpg', 'c.jpg'] as $name) {
            $path = "products/sample/{$name}";
            Storage::disk('public')->put($path, 'fake');
            $urls[] = 'https://example.com/storage/'.$path;
        }

        return $urls;
    }

    private function vendorWithApplication(string $email = 'vendor@example.com'): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Vendor,
            'email' => $email,
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $email,
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        return $user;
    }
}
