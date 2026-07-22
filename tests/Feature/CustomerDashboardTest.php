<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_shows_account_home(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('orderCount', 0)
                ->has('recentOrders', 0)
            );
    }

    public function test_vendor_visiting_dashboard_is_redirected_to_inventory(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertRedirect(route('vendor.inventory.index'));
    }
}
