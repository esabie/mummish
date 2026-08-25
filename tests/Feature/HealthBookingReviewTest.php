<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendHealthBookingReviewInviteSms;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalAvailability;
use App\Models\HealthProfessionalReview;
use App\Models\HealthProfessionalService;
use App\Models\User;
use App\Services\HealthBookingReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class HealthBookingReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_finds_completed_paid_booking(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeCompletedBooking($professional, $service);

        $response = $this->post(route('health-services.bookings.review.lookup'), [
            'reference' => $booking->reference,
            'patient_email' => $booking->patient_email,
        ]);

        $response->assertRedirect(route('health-services.bookings.review.show', $booking));

        $this->get(route('health-services.bookings.review.show', $booking))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HealthServices/ReviewForm')
                ->where('booking.can_review', true)
            );
    }

    public function test_lookup_rejects_incomplete_booking(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeCompletedBooking($professional, $service, status: 'confirmed');

        $this->post(route('health-services.bookings.review.lookup'), [
            'reference' => $booking->reference,
            'patient_email' => $booking->patient_email,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('health_professional_reviews', 0);
    }

    public function test_patient_can_submit_review_and_updates_professional_rating(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeCompletedBooking($professional, $service);

        $this->post(route('health-services.bookings.review.lookup'), [
            'reference' => $booking->reference,
            'patient_email' => $booking->patient_email,
        ]);

        $this->post(route('health-services.bookings.review.store', $booking), [
            'rating' => 5,
            'comment' => 'Very helpful and patient with my child.',
        ])
            ->assertRedirect(route('health-services.bookings.review.show', $booking))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('health_professional_reviews', [
            'health_booking_id' => $booking->id,
            'health_professional_id' => $professional->id,
            'rating' => 5,
            'comment' => 'Very helpful and patient with my child.',
        ]);

        $professional->refresh();
        $this->assertSame(1, $professional->review_count);
        $this->assertSame('5.00', $professional->rating);

        $this->post(route('health-services.bookings.review.store', $booking), [
            'rating' => 4,
            'comment' => 'Duplicate attempt.',
        ])
            ->assertRedirect(route('health-services.bookings.review.show', $booking))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('health_professional_reviews', 1);
    }

    public function test_professional_profile_shows_published_reviews(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeCompletedBooking($professional, $service);

        HealthProfessionalReview::query()->create([
            'health_booking_id' => $booking->id,
            'health_professional_id' => $professional->id,
            'patient_name' => $booking->patient_name,
            'rating' => 4,
            'comment' => 'Great consultation.',
        ]);

        app(HealthBookingReviewService::class)->recalculateRating($professional);

        $this->get(route('health-services.show', $professional->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HealthServices/Show')
                ->where('professional.review_count', 1)
                ->where('professional.rating', 4)
                ->has('professional.reviews', 1)
                ->where('professional.reviews.0.comment', 'Great consultation.')
            );
    }

    public function test_complete_booking_dispatches_review_invite_sms(): void
    {
        Bus::fake([SendHealthBookingReviewInviteSms::class]);

        [$professional, $service, $user] = $this->createBookableProfessional();
        $booking = $this->makeCompletedBooking($professional, $service, status: 'confirmed');

        $this->actingAs($user)
            ->post(route('health-professionals.bookings.complete', [$professional, $booking]))
            ->assertRedirect()
            ->assertSessionHas('success');

        Bus::assertDispatched(
            SendHealthBookingReviewInviteSms::class,
            fn (SendHealthBookingReviewInviteSms $job) => $job->bookingId === $booking->id,
        );
    }

    /**
     * @return array{0: HealthProfessional, 1: HealthProfessionalService, 2: User}
     */
    private function createBookableProfessional(): array
    {
        $user = User::factory()->create([
            'email' => 'provider-'.uniqid('', true).'@example.com',
            'role' => UserRole::HealthProfessional,
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Test Provider',
            'slug' => 'dr-test-provider-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test provider profile for review flow coverage.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual', 'In person'],
            'is_active' => true,
            'approval_status' => \App\Enums\HealthProfessionalApprovalStatus::Approved,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Child Wellness Consultation',
            'visit_mode' => 'Virtual',
            'price_cedis' => 250,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        HealthProfessionalAvailability::create([
            'health_professional_id' => $professional->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        return [$professional, $service, $user];
    }

    private function makeCompletedBooking(
        HealthProfessional $professional,
        HealthProfessionalService $service,
        string $status = 'completed',
    ): HealthBooking {
        return HealthBooking::create([
            'reference' => 'HB-REV001',
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => now()->addWeek()->next('Monday')->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => $status,
            'payment_status' => 'paid',
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paid_at' => now(),
            'confirmed_at' => now(),
        ]);
    }
}
