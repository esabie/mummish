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

class SendHealthBookingConfirmedSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        Log::info('SendHealthBookingConfirmedSms: job started.', [
            'attempt' => $this->attempts(),
            'booking_id' => $this->bookingId,
        ]);

        $booking = HealthBooking::query()
            ->with(['professional', 'service'])
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('SendHealthBookingConfirmedSms: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        if ($booking->status !== 'confirmed') {
            Log::info('SendHealthBookingConfirmedSms: skipped — booking not confirmed.', [
                'booking_id' => $booking->id,
                'status' => $booking->status,
            ]);

            return;
        }

        $phone = trim((string) $booking->patient_phone);

        if ($phone === '') {
            Log::warning('SendHealthBookingConfirmedSms: patient phone missing.', [
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
        $serviceName = $booking->service?->name;

        $serviceBit = $serviceName ? " {$serviceName}" : '';
        $prepNote = $this->prepNoteForBooking($booking);

        $message = "Hi {$firstName}, your {$appName} booking {$booking->reference} with {$provider} is confirmed:{$serviceBit} on {$when} ({$visitMode}). {$prepNote}";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthBookingConfirmedSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthBookingConfirmedSms: SMS sent.', [
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

    private function prepNoteForBooking(HealthBooking $booking): string
    {
        if ($booking->isInPerson()) {
            $parts = ['Please report at least 30 minutes before your booking time.'];

            if (filled($booking->meeting_location)) {
                $parts[] = 'Location: '.$booking->meeting_location;
            }

            if (filled($booking->meeting_whatsapp)) {
                $parts[] = 'WhatsApp: '.$booking->meeting_whatsapp;
            }

            if (filled($booking->logistics_notes)) {
                $parts[] = $booking->logistics_notes;
            }

            return implode(' ', $parts);
        }

        $joinUrl = $booking->sessionJoinUrl();
        if ($joinUrl) {
            $expiresAt = ($booking->appointmentStartsAt() ?? now())->copy()->addHours(4);
            $ttlMinutes = max(120, (int) ceil(max(0, $expiresAt->getTimestamp() - now()->getTimestamp()) / 60));
            $shortUrl = app(ShortLinkService::class)->create($joinUrl, $ttlMinutes);

            $note = "Join your session here: {$shortUrl}. Please use a stable internet connection and a quiet space.";
        } else {
            $note = 'Please ensure you have a stable internet connection and are in a quiet environment for the session.';
        }

        if (filled($booking->meeting_whatsapp)) {
            $note .= ' WhatsApp: '.$booking->meeting_whatsapp.'.';
        }

        if (filled($booking->logistics_notes)) {
            $note .= ' '.$booking->logistics_notes;
        }

        return $note;
    }
}
