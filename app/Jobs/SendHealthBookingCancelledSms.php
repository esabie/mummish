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

class SendHealthBookingCancelledSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthBookingCancelledSms: job started.', [
            'attempt' => $this->attempts(),
            'booking_id' => $this->bookingId,
        ]);

        $booking = HealthBooking::query()
            ->with(['professional', 'service'])
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('SendHealthBookingCancelledSms: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        if ($booking->status !== 'cancelled') {
            Log::info('SendHealthBookingCancelledSms: skipped — booking not cancelled.', [
                'booking_id' => $booking->id,
                'status' => $booking->status,
            ]);

            return;
        }

        $phone = trim((string) $booking->patient_phone);

        if ($phone === '') {
            Log::warning('SendHealthBookingCancelledSms: patient phone missing.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $booking->patient_name, ' ') ?: 'there');
        $appName = config('app.name', 'Mummish');
        $provider = $booking->professional?->name ?? 'your provider';
        $when = $this->formatAppointment($booking);
        $reason = trim((string) ($booking->cancellation_reason ?? ''));
        $reasonBit = $reason !== '' ? " Reason: {$reason}." : '';

        $message = "Hi {$firstName}, your {$appName} booking {$booking->reference} with {$provider} for {$when} has been cancelled.{$reasonBit}";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthBookingCancelledSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthBookingCancelledSms: SMS sent.', [
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
