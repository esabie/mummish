<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Database\Seeder;

class VendorDemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('role', UserRole::Vendor)->first();

        if ($user === null) {
            $this->command?->warn('No vendor user found. Create a vendor account first.');

            return;
        }

        if ($user->vendorApplication === null) {
            VendorApplication::create([
                'user_id' => $user->id,
                'first_name' => 'Demo',
                'last_name' => 'Vendor',
                'shop_name' => 'Demo Shop',
                'business_email' => $user->email,
                'phone' => '0241234567',
                'category' => 'toys_development',
                'terms_accepted' => true,
                'status' => VendorApplicationStatus::Pending,
            ]);
        }

        if (Product::query()->where('user_id', $user->id)->exists()) {
            $this->command?->info('Vendor already has products — skipping demo inventory.');

            return;
        }

        $samples = [
            ['title' => 'Organic Cotton Onesie', 'sku' => 'LH-TOY-001', 'category' => 'clothing_footwear', 'price_cents' => 2400, 'stock' => 42, 'status' => ProductStatus::Active, 'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd046?w=200&h=200&fit=crop'],
            ['title' => 'Wooden Stacking Rings', 'sku' => 'LH-TOY-042', 'category' => 'toys_development', 'price_cents' => 1850, 'stock' => 18, 'status' => ProductStatus::Active, 'image' => 'https://images.unsplash.com/photo-1558060379-7e0cd63e99e7?w=200&h=200&fit=crop'],
            ['title' => 'Muslin Swaddle Set (3pk)', 'sku' => 'LH-NUR-112', 'category' => 'sleep_nursery', 'price_cents' => 3200, 'stock' => 4, 'status' => ProductStatus::Active, 'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd046?w=200&h=200&fit=crop'],
            ['title' => 'Silicone Feeding Set', 'sku' => 'LH-FED-008', 'category' => 'baby_care', 'price_cents' => 4500, 'stock' => 0, 'status' => ProductStatus::Draft, 'image' => null],
            ['title' => 'Linen Summer Dress', 'sku' => 'LH-APP-221', 'category' => 'clothing_footwear', 'price_cents' => 5500, 'stock' => 12, 'status' => ProductStatus::Active, 'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=200&h=200&fit=crop'],
            ['title' => 'Plush Bunny Rattle', 'sku' => 'LH-TOY-089', 'category' => 'toys_development', 'price_cents' => 1200, 'stock' => 3, 'status' => ProductStatus::Active, 'image' => 'https://images.unsplash.com/photo-1587654780291-39fd940a7be0?w=200&h=200&fit=crop'],
            ['title' => 'Board Book: First Words', 'sku' => 'LH-BOK-015', 'category' => 'toys_development', 'price_cents' => 950, 'stock' => 25, 'status' => ProductStatus::Active, 'image' => null],
            ['title' => 'Knit Cardigan', 'sku' => 'LH-APP-198', 'category' => 'clothing_footwear', 'price_cents' => 3800, 'stock' => 8, 'status' => ProductStatus::Draft, 'image' => null],
        ];

        foreach ($samples as $sample) {
            Product::create([
                'user_id' => $user->id,
                'title' => $sample['title'],
                'sku' => $sample['sku'],
                'category' => $sample['category'],
                'price_cents' => $sample['price_cents'],
                'stock_quantity' => $sample['stock'],
                'status' => $sample['status'],
                'image_url' => $sample['image'],
            ]);
        }

        $this->command?->info('Demo inventory seeded for '.$user->email);
    }
}
