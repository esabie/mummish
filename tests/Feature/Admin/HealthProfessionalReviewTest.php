<?php

namespace Tests\Feature\Admin;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Jobs\SendHealthProfessionalStatusSms;
use App\Models\HealthProfessional;
use App\Models\User;
use App\Services\HealthProfessionalReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthProfessionalReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_professional(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = $this->createPendingProfessional();

        app(HealthProfessionalReviewService::class)->approve($professional, $admin);

        $professional->refresh();
        $this->assertSame(HealthProfessionalApprovalStatus::Approved, $professional->approval_status);
        $this->assertTrue($professional->is_active);
        $this->assertNull($professional->rejection_reason);
        $this->assertNotNull($professional->reviewed_at);
        $this->assertSame($admin->id, $professional->reviewed_by_user_id);

        Bus::assertDispatched(SendHealthProfessionalStatusSms::class);
    }

    public function test_admin_can_reject_pending_professional_with_reason(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = $this->createPendingProfessional();

        app(HealthProfessionalReviewService::class)->reject(
            $professional,
            $admin,
            'Credentials could not be verified.',
        );

        $professional->refresh();
        $this->assertSame(HealthProfessionalApprovalStatus::Rejected, $professional->approval_status);
        $this->assertFalse($professional->is_active);
        $this->assertSame('Credentials could not be verified.', $professional->rejection_reason);

        Bus::assertDispatched(SendHealthProfessionalStatusSms::class);
    }

    public function test_admin_can_suspend_approved_professional(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = $this->createPendingProfessional();
        app(HealthProfessionalReviewService::class)->approve($professional, $admin);

        app(HealthProfessionalReviewService::class)->suspend(
            $professional->fresh(),
            $admin,
            'Policy violation.',
        );

        $professional->refresh();
        $this->assertSame(HealthProfessionalApprovalStatus::Suspended, $professional->approval_status);
        $this->assertFalse($professional->is_active);
        $this->assertSame('Policy violation.', $professional->rejection_reason);

        Bus::assertDispatched(SendHealthProfessionalStatusSms::class);
    }

    public function test_unapproved_professionals_are_hidden_from_public_listing(): void
    {
        $pending = $this->createPendingProfessional();
        $pending->update(['is_active' => true]);

        $approved = $this->createPendingProfessional('Dr Approved');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(HealthProfessionalReviewService::class)->approve($approved, $admin);

        $this->get(route('health-services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('professionals', 1)
                ->where('professionals.0.slug', $approved->slug)
            );
    }

    public function test_professional_cannot_self_activate_before_approval(): void
    {
        $user = User::factory()->create(['role' => UserRole::HealthProfessional]);
        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Pending',
            'slug' => 'dr-pending-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'About the provider.',
            'location' => 'Accra',
            'phone' => '0240000001',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'languages' => ['English'],
            'highlights' => ['Warm care'],
            'experience' => '5 years',
            'is_active' => false,
            'approval_status' => HealthProfessionalApprovalStatus::Pending,
        ]);

        $this->actingAs($user)->put(route('health-professionals.update', $professional), [
            'name' => $professional->name,
            'title' => $professional->title,
            'specialty' => $professional->specialty,
            'about' => $professional->about,
            'location' => $professional->location,
            'phone' => $professional->phone,
            'email' => $professional->email,
            'experience_amount' => 5,
            'experience_unit' => 'years',
            'booking_note' => null,
            'visit_modes' => ['Virtual'],
            'languages' => ['English'],
            'highlights' => ['Warm care'],
            'is_active' => true,
            'services' => [
                [
                    'name' => 'Consult',
                    'visit_mode' => 'Virtual',
                    'price_cedis' => 200,
                    'duration_minutes' => 30,
                ],
            ],
            'availability' => [
                [
                    'day_of_week' => 1,
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
            ],
        ])->assertRedirect(route('health-professionals.dashboard'));

        $this->assertFalse($professional->fresh()->is_active);
        $this->assertSame(HealthProfessionalApprovalStatus::Pending, $professional->fresh()->approval_status);
    }

    private function createPendingProfessional(string $name = 'Dr Pending Review'): HealthProfessional
    {
        $user = User::factory()->create([
            'email' => 'hp-'.uniqid('', true).'@example.com',
            'role' => UserRole::HealthProfessional,
        ]);

        return HealthProfessional::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test profile.',
            'location' => 'Accra',
            'phone' => '0241111111',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => false,
            'approval_status' => HealthProfessionalApprovalStatus::Pending,
        ]);
    }
}
