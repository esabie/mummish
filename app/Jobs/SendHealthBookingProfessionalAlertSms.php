<?php

namespace App\Jobs;

use App\Models\HealthBooking;
use App\Services\MnotifySmsService;
use App\Services\ShortLinkService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthBookingProfessionalAlertSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthBookingProfessionalAlertSms: job started.', [
            'attempt' => $this->attempts(),
            'booking_id' => $this->bookingId,
        ]);

        $booking = HealthBooking::query()
            ->with(['professional', 'service'])
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('SendHealthBookingProfessionalAlertSms: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        if ($booking->status !== 'pending' || ! $booking->isPaid()) {
            Log::info('SendHealthBookingProfessionalAlertSms: skipped — booking not a paid pending request.', [
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
            ]);

            return;
        }

        $phone = trim((string) ($booking->professional?->phone ?? ''));

        if ($phone === '') {
            Log::warning('SendHealthBookingProfessionalAlertSms: professional phone missing.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'health_professional_id' => $booking->health_professional_id,
            ]);

            return;
        }

        $firstName = trim(strtok((string) ($booking->professional?->name ?? ''), ' ') ?: 'there');
        $appName = config('app.name', 'Mummish');
        $patient = trim((string) $booking->patient_name) ?: 'a patient';
        $when = $this->formatAppointment($booking);
        $visitMode = $booking->visit_mode ?: 'visit';
        $serviceName = trim((string) ($booking->service?->name ?? ''));
        $serviceBit = $serviceName !== '' ? " {$serviceName}," : '';

        $dashboardUrl = route('health-professionals.dashboard', absolute: true);
        $shortDashboardUrl = app(ShortLinkService::class)->create($dashboardUrl, 60 * 24 * 7);

        $message = "Hi {$firstName}, new paid {$appName} Health Services booking {$booking->reference}:{$serviceBit} {$patient} on {$when} ({$visitMode}). Confirm here: {$shortDashboardUrl}";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthBookingProfessionalAlertSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthBookingProfessionalAlertSms: SMS sent.', [
            'attempt' => $this->attempts(),
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }

    private function formatAppointment(HealthBooking $booking): string
    {
        $date = $booking->appointment_date instanceof Carbon
            ? $booking->appointment_date->copy()
            : Carbon::parse((string) $booking->appointment_date);

        $time = Carbon::parse((string) $booking->appointment_time)->format('g:i A');

        return $date->format('D, j M').' at '.$time;
    }
}
