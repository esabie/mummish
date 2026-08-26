<?php

namespace Tests\Feature;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Models\HealthProfessional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthProfessionalSignupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Mensah',
            'title' => 'Pediatric Nurse',
            'specialty' => 'Pediatrics',
            'about' => 'I support families with newborn care and child wellness visits.',
            'location' => 'Accra',
            'phone' => '0543842567',
            'email' => 'ama.health@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'experience_amount' => 5,
            'experience_unit' => 'years',
            'image' => UploadedFile::fake()->image('profile.jpg', 800, 800),
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_guest_can_register_as_health_professional(): void
    {
        Storage::fake('public');

        $response = $this->post(route('health-professionals.signup.store'), $this->validPayload());

        $professional = HealthProfessional::query()->first();

        $this->assertNotNull($professional);
        $response->assertRedirect(route('health-professionals.edit', $professional));

        $this->assertDatabaseHas('users', [
            'email' => 'ama.health@example.com',
            'phone' => '0543842567',
            'role' => UserRole::HealthProfessional->value,
        ]);

        $this->assertSame(HealthProfessionalApprovalStatus::Pending, $professional->approval_status);
        $this->assertFalse($professional->is_active);
        $this->assertNotNull($professional->image_path);
        Storage::disk('public')->assertExists($professional->image_path);
    }

    public function test_signup_rejects_existing_customer_email_with_clear_message(): void
    {
        Storage::fake('public');

        User::factory()->create([
            'email' => 'taken@example.com',
            'role' => UserRole::Customer,
        ]);

        $this->post(route('health-professionals.signup.store'), $this->validPayload([
            'email' => 'taken@example.com',
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('health_professionals', 0);
    }

    public function test_signup_requires_profile_image(): void
    {
        $this->post(route('health-professionals.signup.store'), $this->validPayload([
            'image' => null,
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('health_professionals', 0);
    }
}
