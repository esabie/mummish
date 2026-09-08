<?php

namespace Tests\Feature;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Jobs\SendHealthBookingConfirmedSms;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthBookingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_lookup_own_booking(): void
    {
        [$professional, $user, $booking] = $this->createProfessionalWithBooking();

        $this->actingAs($user)
            ->get(route('health-professionals.bookings.lookup'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('HealthServices/BookingLookup'));

        $this->actingAs($user)
            ->post(route('health-professionals.bookings.lookup.submit'), [
                'reference' => $booking->reference,
                'patient_email' => $booking->patient_email,
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HealthServices/BookingLookup')
                ->where('result.reference', $booking->reference)
                ->where('result.patient_name', $booking->patient_name));
    }

    public function test_professional_cannot_lookup_another_professionals_booking(): void
    {
        [, $user] = $this->createProfessionalWithBooking('HB-OWN001');
        [, , $otherBooking] = $this->createProfessionalWithBooking('HB-OTH001', 'other@example.com');

        $this->actingAs($user)
            ->from(route('health-professionals.bookings.lookup'))
            ->post(route('health-professionals.bookings.lookup.submit'), [
                'reference' => $otherBooking->reference,
                'patient_email' => $otherBooking->patient_email,
            ])
            ->assertRedirect(route('health-professionals.bookings.lookup'))
            ->assertSessionHas('error');
    }

    public function test_confirm_virtual_booking_requires_google_meet_link(): void
    {
        Bus::fake([SendHealthBookingConfirmedSms::class]);

        [$professional, $user, $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-VIRT01',
            visitMode: 'Virtual',
        );

        $this->actingAs($user)
            ->from(route('health-professionals.dashboard'))
            ->post(route('health-professionals.bookings.confirm', [$professional, $booking]), [
                'logistics_notes' => 'Bring vaccination card if available',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('meeting_url');

        $this->assertSame('pending', $booking->fresh()->status);
        Bus::assertNotDispatched(SendHealthBookingConfirmedSms::class);
    }

    public function test_confirm_virtual_booking_stores_google_meet_link(): void
    {
        Bus::fake([SendHealthBookingConfirmedSms::class]);

        [$professional, $user, $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-VIRT02',
            visitMode: 'Virtual',
        );

        $meetUrl = 'https://meet.google.com/abc-defg-hij';

        $this->actingAs($user)
            ->post(route('health-professionals.bookings.confirm', [$professional, $booking]), [
                'meeting_url' => $meetUrl,
                'logistics_notes' => 'Bring vaccination card if available',
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame($meetUrl, $booking->meeting_url);
        $this->assertSame('Bring vaccination card if available', $booking->logistics_notes);
        $this->assertSame($meetUrl, $booking->sessionJoinUrl());

        Bus::assertDispatched(SendHealthBookingConfirmedSms::class);
    }

    public function test_professional_can_add_google_meet_link_to_confirmed_booking(): void
    {
        [$professional, $user, $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-ADDMT',
            visitMode: 'Virtual',
            status: 'confirmed',
            appointmentDate: now()->toDateString(),
            appointmentTime: now()->format('H:i:s'),
        );

        $booking->forceFill([
            'confirmed_at' => now(),
        ])->save();

        $meetUrl = 'https://meet.google.com/aaa-bbbb-ccc';

        $this->actingAs($user)
            ->post(route('health-professionals.bookings.meeting-url', [$professional, $booking]), [
                'meeting_url' => $meetUrl,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame($meetUrl, $booking->meeting_url);
        $this->assertSame($meetUrl, $booking->sessionJoinUrl());
    }

    public function test_confirm_in_person_requires_location(): void
    {
        Bus::fake();

        [$professional, $user, $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-INP001',
            visitMode: 'In person',
        );

        $this->actingAs($user)
            ->from(route('health-professionals.dashboard'))
            ->post(route('health-professionals.bookings.confirm', [$professional, $booking]), [])
            ->assertRedirect()
            ->assertSessionHasErrors('meeting_location');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_confirm_in_person_stores_location(): void
    {
        Bus::fake([SendHealthBookingConfirmedSms::class]);

        [$professional, $user, $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-INP002',
            visitMode: 'In person',
        );

        $this->actingAs($user)
            ->post(route('health-professionals.bookings.confirm', [$professional, $booking]), [
                'meeting_location' => 'Ridge Hospital, Accra — Wing B',
                'meeting_whatsapp' => '0249998888',
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('Ridge Hospital, Accra — Wing B', $booking->meeting_location);
        $this->assertSame('0249998888', $booking->meeting_whatsapp);
        $this->assertNull($booking->meeting_url);
        Bus::assertDispatched(SendHealthBookingConfirmedSms::class);
    }

    public function test_can_join_session_within_window_with_meet_link(): void
    {
        [, , $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-JOIN1',
            visitMode: 'Virtual',
            status: 'confirmed',
            appointmentDate: now()->toDateString(),
            appointmentTime: now()->format('H:i:s'),
        );

        $booking->forceFill([
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            'confirmed_at' => now(),
        ])->save();

        $this->assertTrue($booking->canJoinSession());
        $this->assertSame('https://meet.google.com/abc-defg-hij', $booking->sessionJoinUrl());
    }

    public function test_cannot_join_session_outside_window(): void
    {
        [, , $booking] = $this->createProfessionalWithBooking(
            reference: 'HB-JOIN2',
            visitMode: 'Virtual',
            status: 'confirmed',
            appointmentDate: now()->addDays(3)->toDateString(),
            appointmentTime: '10:00:00',
        );

        $booking->forceFill([
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            'confirmed_at' => now(),
        ])->save();

        $this->assertFalse($booking->canJoinSession());
    }

    /**
     * @return array{0: HealthProfessional, 1: User, 2: HealthBooking}
     */
    private function createProfessionalWithBooking(
        string $reference = 'HB-TEST01',
        string $patientEmail = 'patient@example.com',
        string $visitMode = 'Virtual',
        string $status = 'pending',
        ?string $appointmentDate = null,
        ?string $appointmentTime = null,
    ): array {
        $user = User::factory()->create([
            'role' => UserRole::HealthProfessional,
            'email' => 'pro-'.uniqid('', true).'@example.com',
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Session',
            'slug' => 'dr-session-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test',
            'location' => 'Accra',
            'phone' => '0240000099',
            'email' => $user->email,
            'visit_modes' => ['Virtual', 'In person'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Consultation',
            'visit_mode' => $visitMode,
            'price_cedis' => 150,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $booking = HealthBooking::create([
            'reference' => $reference,
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => $patientEmail,
            'patient_phone' => '0241234567',
            'appointment_date' => $appointmentDate ?? now()->addDay()->toDateString(),
            'appointment_time' => $appointmentTime ?? '09:00:00',
            'visit_mode' => $visitMode,
            'status' => $status,
            'payment_status' => 'paid',
            'amount_cents' => 15000,
            'paid_at' => now(),
        ]);

        return [$professional, $user, $booking];
    }
}
