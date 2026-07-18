<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_promo_codes_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/promo-codes')
            ->assertOk();
    }

    public function test_admin_can_view_create_promo_code_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/promo-codes/create')
            ->assertOk();
    }

    public function test_non_admin_cannot_access_promo_codes(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/promo-codes')
            ->assertForbidden();
    }
}
