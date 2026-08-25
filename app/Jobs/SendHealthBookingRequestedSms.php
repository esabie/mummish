<?php

namespace App\Jobs;

use App\Models\HealthBooking;
use App\Services\MnotifySmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthBookingRequestedSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthBookingRequestedSms: job started.', [
            'attempt' => $this->attempts(),
            'booking_id' => $this->bookingId,
        ]);

        $booking = HealthBooking::query()
            ->with(['professional', 'service'])
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('SendHealthBookingRequestedSms: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        $phone = trim((string) $booking->patient_phone);

        if ($phone === '') {
            Log::warning('SendHealthBookingRequestedSms: patient phone missing.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $booking->patient_name, ' ') ?: 'there');
        $appName = config('app.name', 'Mummish');
        $provider = $booking->professional?->name ?? 'your provider';
        $when = $this->formatAppointment($booking);
        $visitMode = $booking->visit_mode ?: 'visit';

        $message = "Hi {$firstName}, payment received for {$appName} Health Services booking {$booking->reference} with {$provider} on {$when} ({$visitMode}).";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthBookingRequestedSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthBookingRequestedSms: SMS sent.', [
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
