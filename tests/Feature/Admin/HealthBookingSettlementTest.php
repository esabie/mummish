<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Models\User;
use App\Services\HealthBookingSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthBookingSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_payout_is_due_only_after_completed_paid_booking(): void
    {
        $booking = $this->createBooking(status: 'confirmed', paymentStatus: 'paid');

        $this->assertFalse($booking->professionalPayoutIsDue());

        $booking->update(['status' => 'completed']);

        $this->assertTrue($booking->fresh()->professionalPayoutIsDue());
        $this->assertTrue(app(HealthBookingSettlementService::class)->markPaid($booking->fresh()));
        $this->assertFalse($booking->fresh()->professionalPayoutIsDue());
        $this->assertTrue($booking->fresh()->isProfessionalPaid());
        $this->assertNotNull($booking->fresh()->professional_paid_at);
    }

    public function test_cannot_mark_unpaid_or_incomplete_booking_as_paid_out(): void
    {
        $unpaid = $this->createBooking(status: 'completed', paymentStatus: 'pending');
        $this->assertFalse(app(HealthBookingSettlementService::class)->markPaid($unpaid));

        $confirmed = $this->createBooking(status: 'confirmed', paymentStatus: 'paid');
        $this->assertFalse(app(HealthBookingSettlementService::class)->markPaid($confirmed));
    }

    public function test_admin_can_undo_professional_payout_mark(): void
    {
        $booking = $this->createBooking(status: 'completed', paymentStatus: 'paid');
        $service = app(HealthBookingSettlementService::class);

        $this->assertTrue($service->markPaid($booking));
        $this->assertTrue($service->markUnpaid($booking->fresh()));
        $this->assertNull($booking->fresh()->professional_paid_at);
        $this->assertTrue($booking->fresh()->professionalPayoutIsDue());
    }

    public function test_admin_health_bookings_page_loads(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $due = $this->createBooking(status: 'completed', paymentStatus: 'paid', reference: 'HB-DUE001');

        $this->actingAs($admin)
            ->get('/admin/health-bookings')
            ->assertOk()
            ->assertSee('HB-DUE001')
            ->assertSee('Due');
    }

    private function createBooking(
        string $status,
        string $paymentStatus,
        ?string $reference = null,
    ): HealthBooking {
        $user = User::factory()->create([
            'email' => 'provider-settle-'.uniqid('', true).'@example.com',
            'role' => UserRole::HealthProfessional,
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Settlement Test',
            'slug' => 'dr-settlement-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Settlement test provider.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Consultation',
            'visit_mode' => 'Virtual',
            'price_cedis' => 250,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return HealthBooking::create([
            'reference' => $reference ?? ('HB-'.strtoupper(substr(uniqid(), -6))),
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
            'confirmed_at' => in_array($status, ['confirmed', 'completed'], true) ? now() : null,
        ]);
    }
}
