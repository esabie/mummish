<?php

namespace App\Services;

use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalReview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

class HealthBookingReviewService
{
    public const SESSION_KEY = 'verified_health_booking_ids';

    public function findForLookup(string $reference, string $email): ?HealthBooking
    {
        $reference = strtoupper(trim($reference));
        $email = strtolower(trim($email));

        if ($reference === '' || $email === '') {
            return null;
        }

        return HealthBooking::query()
            ->with(['professional', 'service', 'review'])
            ->whereRaw('UPPER(reference) = ?', [$reference])
            ->whereRaw('LOWER(patient_email) = ?', [$email])
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->first();
    }

    public function canReview(HealthBooking $booking): bool
    {
        $booking->loadMissing('review');

        return $booking->status === 'completed'
            && $booking->isPaid()
            && $booking->review === null;
    }

    public function markVerified(HealthBooking $booking): void
    {
        $ids = session(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            $ids = [];
        }

        $ids[] = $booking->id;

        session([self::SESSION_KEY => array_values(array_unique($ids))]);
    }

    public function canView(Request $request, HealthBooking $booking): bool
    {
        if ($booking->status !== 'completed' || ! $booking->isPaid()) {
            return false;
        }

        $verifiedIds = session(self::SESSION_KEY, []);

        if (! is_array($verifiedIds)) {
            return false;
        }

        return in_array($booking->id, $verifiedIds, true);
    }

    /**
     * @throws RuntimeException
     */
    public function submitReview(HealthBooking $booking, int $rating, ?string $comment): HealthProfessionalReview
    {
        if (! $this->canReview($booking)) {
            throw new RuntimeException('This booking is not eligible for a review.');
        }

        $review = HealthProfessionalReview::query()->create([
            'health_booking_id' => $booking->id,
            'health_professional_id' => $booking->health_professional_id,
            'patient_name' => $booking->patient_name,
            'rating' => $rating,
            'comment' => filled($comment) ? trim($comment) : null,
        ]);

        $this->recalculateRating($booking->professional);

        return $review;
    }

    public function recalculateRating(HealthProfessional $professional): void
    {
        $stats = HealthProfessionalReview::query()
            ->where('health_professional_id', $professional->id)
            ->published()
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
            ->first();

        $count = (int) ($stats->review_count ?? 0);
        $average = $count > 0 ? round((float) $stats->average_rating, 2) : 0;

        $professional->update([
            'review_count' => $count,
            'rating' => $average,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatReviewPagePayload(HealthBooking $booking): array
    {
        $booking->loadMissing(['professional', 'service', 'review']);

        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'patient_name' => $booking->patient_name,
            'appointment_date' => $booking->appointment_date?->toDateString(),
            'appointment_time' => Carbon::parse((string) $booking->appointment_time)->format('g:i A'),
            'visit_mode' => $booking->visit_mode,
            'service_name' => $booking->service?->name,
            'professional' => [
                'name' => $booking->professional?->name,
                'slug' => $booking->professional?->slug,
            ],
            'can_review' => $this->canReview($booking),
            'existing_review' => $booking->review
                ? $this->formatReviewCard($booking->review)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatReviewCard(HealthProfessionalReview $review): array
    {
        $displayName = $this->formatPatientDisplayName($review->patient_name);
        $initial = strtoupper(substr($displayName, 0, 1));

        return [
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'author' => $displayName,
            'initial' => $initial,
            'date' => $review->created_at?->format('j M Y'),
        ];
    }

    public function formatPatientDisplayName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return 'Patient';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.strtoupper(substr($parts[1], 0, 1)).'.';
    }
}
