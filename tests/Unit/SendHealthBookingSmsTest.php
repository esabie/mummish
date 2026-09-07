<?php

namespace Tests\Unit;

use App\Jobs\SendHealthBookingCancelledSms;
use App\Jobs\SendHealthBookingConfirmedSms;
use App\Jobs\SendHealthBookingProfessionalAlertSms;
use App\Jobs\SendHealthBookingRequestedSms;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Models\User;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendHealthBookingSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_booking_requested_sms(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $booking = $this->createBooking('pending');

        (new SendHealthBookingRequestedSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($booking) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Hi Ama')
                && str_contains($message, $booking->reference)
                && str_contains($message, 'Dr Test Provider')
                && str_contains($message, 'payment received')
                && str_contains($message, 'Health Services')
                && ! str_contains($message, 'Heath Services');
        });
    }

    public function test_sends_booking_professional_alert_sms(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $booking = $this->createBooking('pending');
        $booking->forceFill([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ])->save();

        (new SendHealthBookingProfessionalAlertSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($booking) {
            $body = $request->data();
            $message = $body['message'] ?? '';
            $recipients = $body['recipient'] ?? [];
            $recipientList = is_array($recipients) ? implode(',', $recipients) : (string) $recipients;

            return str_contains($message, 'Hi Dr')
                && str_contains($message, $booking->reference)
                && str_contains($message, 'Ama Mensah')
                && str_contains($message, 'new paid')
                && str_contains($message, 'dashboard to confirm')
                && str_contains($recipientList, '0240000000');
        });
    }

    public function test_professional_alert_sms_skips_unpaid_bookings(): void
    {
        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake();

        $booking = $this->createBooking('pending');
        $booking->forceFill(['payment_status' => 'pending'])->save();

        (new SendHealthBookingProfessionalAlertSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertNothingSent();
    }

    public function test_sends_booking_confirmed_sms_for_virtual_visit(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $booking = $this->createBooking('confirmed', 'Virtual');

        (new SendHealthBookingConfirmedSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($booking) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Hi Ama')
                && str_contains($message, $booking->reference)
                && str_contains($message, 'is confirmed')
                && str_contains($message, 'Child Wellness Consultation')
                && str_contains($message, 'stable internet')
                && str_contains($message, 'quiet environment');
        });
    }

    public function test_sends_booking_confirmed_sms_for_in_person_visit(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $booking = $this->createBooking('confirmed', 'In person');

        (new SendHealthBookingConfirmedSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($booking) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Hi Ama')
                && str_contains($message, $booking->reference)
                && str_contains($message, 'is confirmed')
                && str_contains($message, 'report at least 30 minutes');
        });
    }

    public function test_confirmed_sms_skips_pending_bookings(): void
    {
        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake();

        $booking = $this->createBooking('pending');

        (new SendHealthBookingConfirmedSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertNothingSent();
    }

    public function test_sends_booking_cancelled_sms(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $booking = $this->createBooking('cancelled', 'Virtual');
        $booking->update(['cancellation_reason' => 'Provider unavailable']);

        (new SendHealthBookingCancelledSms($booking->id))->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) use ($booking) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return str_contains($message, 'Hi Ama')
                && str_contains($message, $booking->reference)
                && str_contains($message, 'cancelled')
                && str_contains($message, 'Provider unavailable');
        });
    }

    private function createBooking(string $status, string $visitMode = 'Virtual'): HealthBooking
    {
        $user = User::factory()->create([
            'email' => 'provider-sms-'.uniqid('', true).'@example.com',
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Test Provider',
            'slug' => 'dr-test-provider-sms-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test provider for SMS coverage.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual', 'In person'],
            'is_active' => true,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Child Wellness Consultation',
            'visit_mode' => $visitMode,
            'price_cedis' => 250,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return HealthBooking::create([
            'reference' => 'HB-'.strtoupper(substr(uniqid(), -6)),
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => now()->addWeek()->next('Monday')->toDateString(),
            'appointment_time' => '09:00',
            'visit_mode' => $visitMode,
            'status' => $status,
            'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);
    }
}
