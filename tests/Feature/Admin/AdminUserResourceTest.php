<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_another_admin_with_phone(): void
    {
        $admin = User::factory()->admin()->create([
            'phone' => '0201111111',
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\AdminUserResource\Pages\CreateAdminUser::class)
            ->fillForm([
                'name' => 'Second Admin',
                'email' => 'second@mummish.com',
                'phone' => '0202222222',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'second@mummish.com')->first();

        $this->assertNotNull($created);
        $this->assertSame(UserRole::Admin, $created->role);
        $this->assertSame('0202222222', $created->phone);
        $this->assertSame('0202222222', $created->passwordResetPhone());
    }

    public function test_admin_users_index_is_reachable(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0201111111']);

        $this->actingAs($admin)
            ->get('/admin/admin-users')
            ->assertOk();
    }
}
