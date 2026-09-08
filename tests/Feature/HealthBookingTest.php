<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendHealthBookingCancelledSms;
use App\Jobs\SendHealthBookingConfirmedSms;
use App\Jobs\SendHealthBookingProfessionalAlertSms;
use App\Jobs\SendHealthBookingRequestedSms;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalAvailability;
use App\Models\HealthProfessionalService;
use App\Models\User;
use App\Services\HealthBookingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => 'sk_test_dummy',
            'services.paystack.public_key' => 'pk_test_dummy',
            'marketplace.vendor_commission_bps' => 1000,
            'marketplace.health_booking_payment_hold_minutes' => 20,
        ]);
    }

    public function test_guest_starts_health_booking_payment(): void
    {
        Bus::fake([SendHealthBookingRequestedSms::class]);
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-health',
                    'access_code' => 'ACCESS',
                    'reference' => 'ignored-by-client',
                ],
            ], 200),
        ]);

        [$professional, $service] = $this->createBookableProfessional();
        $date = $this->nextMonday();

        $response = $this->post(route('health-services.bookings.store', $professional->slug), [
            'health_professional_service_id' => $service->id,
            'visit_mode' => 'Virtual',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'notes' => 'First visit for my toddler.',
        ]);

        $response->assertRedirect('https://checkout.paystack.com/test-health');

        $booking = HealthBooking::query()->first();

        $this->assertNotNull($booking);
        $this->assertSame('awaiting_payment', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame(25000, $booking->amount_cents);
        $this->assertSame(2500, $booking->commission_cents);
        $this->assertSame(22500, $booking->professional_payout_cents);
        $this->assertNotNull($booking->payment_expires_at);
        $this->assertStringStartsWith('HB-', $booking->reference);

        Bus::assertNotDispatched(SendHealthBookingRequestedSms::class);
    }

    public function test_payment_callback_reserves_booking_and_sends_sms(): void
    {
        Bus::fake([
            SendHealthBookingRequestedSms::class,
            SendHealthBookingProfessionalAlertSms::class,
        ]);

        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeAwaitingPaymentBooking($professional, $service);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => $booking->amount_cents,
                    'id' => 'txn_health_1',
                    'reference' => $booking->paystack_reference,
                ],
            ], 200),
        ]);

        $this->get(route('health-services.bookings.callback', [
            'reference' => $booking->paystack_reference,
        ]))
            ->assertRedirect(route('health-services.show', $professional->slug))
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('pending', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->paid_at);

        Bus::assertDispatched(
            SendHealthBookingRequestedSms::class,
            fn (SendHealthBookingRequestedSms $job) => $job->bookingId === $booking->id,
        );
        Bus::assertDispatched(
            SendHealthBookingProfessionalAlertSms::class,
            fn (SendHealthBookingProfessionalAlertSms $job) => $job->bookingId === $booking->id,
        );
    }

    public function test_booking_rejects_unavailable_slot(): void
    {
        Bus::fake([SendHealthBookingRequestedSms::class]);
        Http::fake();

        [$professional, $service] = $this->createBookableProfessional();
        $date = $this->nextMonday();

        $this->post(route('health-services.bookings.store', $professional->slug), [
            'health_professional_service_id' => $service->id,
            'visit_mode' => 'Virtual',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '23:00',
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
        ])->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('health_bookings', 0);
    }

    public function test_booking_rejects_double_booking_same_slot(): void
    {
        Bus::fake([SendHealthBookingRequestedSms::class]);
        Http::fake();

        [$professional, $service] = $this->createBookableProfessional();
        $date = $this->nextMonday();

        HealthBooking::create([
            'reference' => 'HB-TEST01',
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'First Patient',
            'patient_email' => 'first@example.com',
            'patient_phone' => '0241111111',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => 'pending',
            'payment_status' => 'paid',
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paid_at' => now(),
        ]);

        $this->post(route('health-services.bookings.store', $professional->slug), [
            'health_professional_service_id' => $service->id,
            'visit_mode' => 'Virtual',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'patient_name' => 'Second Patient',
            'patient_email' => 'second@example.com',
            'patient_phone' => '0242222222',
        ])->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('health_bookings', 1);
    }

    public function test_unpaid_payment_hold_blocks_slot(): void
    {
        Http::fake();

        [$professional, $service] = $this->createBookableProfessional();
        $this->makeAwaitingPaymentBooking($professional, $service);

        $this->post(route('health-services.bookings.store', $professional->slug), [
            'health_professional_service_id' => $service->id,
            'visit_mode' => 'Virtual',
            'appointment_date' => $this->nextMonday()->toDateString(),
            'appointment_time' => '09:00',
            'patient_name' => 'Second Patient',
            'patient_email' => 'second@example.com',
            'patient_phone' => '0242222222',
        ])->assertSessionHasErrors('appointment_time');
    }

    public function test_expired_payment_holds_are_released(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $booking = $this->makeAwaitingPaymentBooking($professional, $service);
        $booking->update(['payment_expires_at' => now()->subMinute()]);

        $count = app(HealthBookingPaymentService::class)->expireUnpaidHolds();

        $this->assertSame(1, $count);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('expired', $booking->payment_status);
    }

    public function test_professional_can_confirm_paid_booking_and_dispatches_sms(): void
    {
        Bus::fake([SendHealthBookingConfirmedSms::class]);

        [$professional, $service, $user] = $this->createBookableProfessional();
        $booking = $this->makeUpcomingBooking($professional, $service, 'pending');

        $this->actingAs($user)
            ->from(route('health-professionals.dashboard'))
            ->post(route('health-professionals.bookings.confirm', [$professional, $booking]), [
                'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            ])
            ->assertRedirect(route('health-professionals.dashboard'))
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertSame('https://meet.google.com/abc-defg-hij', $booking->meeting_url);

        Bus::assertDispatched(
            SendHealthBookingConfirmedSms::class,
            fn (SendHealthBookingConfirmedSms $job) => $job->bookingId === $booking->id,
        );
    }

    public function test_professional_can_decline_pending_booking(): void
    {
        Bus::fake([SendHealthBookingCancelledSms::class]);

        [$professional, $service, $user] = $this->createBookableProfessional();
        $booking = $this->makeUpcomingBooking($professional, $service, 'pending');

        $this->actingAs($user)
            ->from(route('health-professionals.schedule'))
            ->post(route('health-professionals.bookings.decline', [$professional, $booking]), [
                'cancellation_reason' => 'No availability that day',
            ])
            ->assertRedirect(route('health-professionals.schedule'))
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('No availability that day', $booking->cancellation_reason);

        Bus::assertDispatched(SendHealthBookingCancelledSms::class);
    }

    public function test_professional_can_cancel_confirmed_booking(): void
    {
        Bus::fake([SendHealthBookingCancelledSms::class]);

        [$professional, $service, $user] = $this->createBookableProfessional();
        $booking = $this->makeUpcomingBooking($professional, $service, 'confirmed');

        $this->actingAs($user)
            ->from(route('health-professionals.schedule'))
            ->post(route('health-professionals.bookings.cancel', [$professional, $booking]))
            ->assertRedirect(route('health-professionals.schedule'))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
        Bus::assertDispatched(SendHealthBookingCancelledSms::class);
    }

    public function test_professional_can_complete_confirmed_booking(): void
    {
        [$professional, $service, $user] = $this->createBookableProfessional();
        $booking = $this->makeUpcomingBooking($professional, $service, 'confirmed');

        $this->actingAs($user)
            ->from(route('health-professionals.schedule'))
            ->post(route('health-professionals.bookings.complete', [$professional, $booking]))
            ->assertRedirect(route('health-professionals.schedule'))
            ->assertSessionHas('success');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_dashboard_and_schedule_require_health_professional(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('health-professionals.dashboard'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('health-professionals.schedule'))
            ->assertForbidden();
    }

    public function test_professional_can_view_dashboard_and_schedule(): void
    {
        [$professional, $service, $user] = $this->createBookableProfessional();
        $this->makeUpcomingBooking($professional, $service, 'pending');

        $this->actingAs($user)
            ->get(route('health-professionals.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HealthServices/Dashboard')
                ->has('professional')
                ->has('stats')
                ->has('pending_bookings')
                ->has('todays_bookings'));

        $this->actingAs($user)
            ->get(route('health-professionals.schedule'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HealthServices/Schedule')
                ->has('bookings')
                ->has('pending_bookings')
                ->has('upcoming')
                ->has('recent')
                ->has('today'));
    }

    public function test_show_page_marks_booked_time_slots(): void
    {
        [$professional, $service] = $this->createBookableProfessional();
        $date = $this->nextMonday();

        HealthBooking::create([
            'reference' => 'HB-SLOT01',
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => 'pending',
            'payment_status' => 'paid',
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paid_at' => now(),
        ]);

        $response = $this->get(route('health-services.show', $professional->slug));
        $response->assertOk();

        $slots = $response->original->getData()['page']['props']['professional']['slots'];
        $day = collect($slots)->firstWhere('date_iso', $date->toDateString());
        $nineAm = collect($day['times'])->firstWhere('label', '9:00 AM');
        $nineThirty = collect($day['times'])->firstWhere('label', '9:30 AM');

        $this->assertTrue($nineAm['booked']);
        $this->assertFalse($nineThirty['booked']);
    }

    private function nextMonday()
    {
        $date = now()->next('Monday');
        if ($date->isPast() || $date->isToday()) {
            $date = now()->addWeek()->next('Monday');
        }

        return $date;
    }

    private function makeAwaitingPaymentBooking(
        HealthProfessional $professional,
        HealthProfessionalService $service,
    ): HealthBooking {
        $date = $this->nextMonday();

        return HealthBooking::create([
            'reference' => 'HB-PAY001',
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => 'awaiting_payment',
            'payment_status' => 'pending',
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paystack_reference' => 'HB-PAY001',
            'payment_expires_at' => now()->addMinutes(20),
        ]);
    }

    private function makeUpcomingBooking(
        HealthProfessional $professional,
        HealthProfessionalService $service,
        string $status,
    ): HealthBooking {
        $date = $this->nextMonday();

        return HealthBooking::create([
            'reference' => 'HB-'.strtoupper(substr(uniqid(), -6)),
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => 'Virtual',
            'status' => $status,
            'payment_status' => 'paid',
            'amount_cents' => 25000,
            'commission_cents' => 2500,
            'professional_payout_cents' => 22500,
            'paid_at' => now(),
            'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);
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
            'about' => 'Test provider profile for booking flow coverage.',
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
}
