<?php

namespace Tests\Feature\Vendor;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestProductImage;
use Tests\TestCase;

class VendorProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_check_image_quality(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $user = $this->vendorWithApplication();

        $response = $this->actingAs($user)->postJson(route('vendor.inventory.check-image'), [
            'image' => TestProductImage::sharpJpeg('product.jpg'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('pass', true);
        $response->assertJsonStructure(['width', 'height', 'sharpness_score', 'issues', 'messages']);
    }

    public function test_guest_cannot_check_image_quality(): void
    {
        $response = $this->postJson(route('vendor.inventory.check-image'), [
            'image' => TestProductImage::sharpJpeg('product.jpg'),
        ]);

        $response->assertUnauthorized();
    }

    private function vendorWithApplication(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Vendor,
            'email' => 'vendor@example.com',
        ]);

        VendorApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => 'vendor@example.com',
            'phone' => '0241234567',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        return $user;
    }
}
