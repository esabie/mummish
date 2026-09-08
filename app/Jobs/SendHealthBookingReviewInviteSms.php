<?php

namespace App\Jobs;

use App\Models\HealthBooking;
use App\Services\MnotifySmsService;
use App\Services\ShortLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthBookingReviewInviteSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(MnotifySmsService $mnotifySms): void
    {
        $booking = HealthBooking::query()
            ->with(['professional', 'review'])
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('SendHealthBookingReviewInviteSms: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        if ($booking->status !== 'completed' || ! $booking->isPaid()) {
            Log::info('SendHealthBookingReviewInviteSms: skipped — booking not completed and paid.', [
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
            ]);

            return;
        }

        if ($booking->review !== null) {
            Log::info('SendHealthBookingReviewInviteSms: skipped — review already exists.', [
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $phone = trim((string) $booking->patient_phone);

        if ($phone === '') {
            Log::warning('SendHealthBookingReviewInviteSms: patient phone missing.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
            ]);

            return;
        }

        $firstName = trim(strtok((string) $booking->patient_name, ' ') ?: 'there');
        $provider = $booking->professional?->name ?? 'your provider';
        $reviewUrl = route('health-services.bookings.review', [
            'reference' => $booking->reference,
            'email' => $booking->patient_email,
        ], absolute: true);

        // Give patients time to leave a review after the visit.
        $shortUrl = app(ShortLinkService::class)->create($reviewUrl, 60 * 24 * 30);

        $message = "Hi {$firstName}, thanks for visiting {$provider} via ".config('app.name', 'Mummish').". Share your experience: {$shortUrl} (ref {$booking->reference})";

        $sent = $mnotifySms->send($phone, $message);

        if (! $sent) {
            Log::warning('SendHealthBookingReviewInviteSms: failed to send SMS.', [
                'attempt' => $this->attempts(),
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
            ]);

            return;
        }

        Log::info('SendHealthBookingReviewInviteSms: SMS sent.', [
            'attempt' => $this->attempts(),
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'phone_masked' => MnotifySmsService::maskPhoneForLog($phone),
        ]);
    }
}
