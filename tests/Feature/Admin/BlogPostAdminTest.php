<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_blog_posts_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/blog-posts')
            ->assertOk();
    }

    public function test_admin_can_view_create_blog_post_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/blog-posts/create')
            ->assertOk();
    }

    public function test_non_admin_cannot_access_blog_posts(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/blog-posts')
            ->assertForbidden();
    }
}
