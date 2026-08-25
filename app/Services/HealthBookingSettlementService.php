<?php

namespace App\Services;

use App\Models\HealthBooking;
use App\Support\AppLog;
use Illuminate\Support\Collection;

class HealthBookingSettlementService
{
    public function markPaid(HealthBooking $booking): bool
    {
        $marked = $booking->markProfessionalPaid();

        if ($marked) {
            AppLog::info('[HealthBooking] Professional payout marked paid.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'professional_payout_cents' => $booking->professional_payout_cents,
            ]);
        }

        return $marked;
    }

    /**
     * @param  Collection<int, HealthBooking>|iterable<int, HealthBooking>  $bookings
     * @return int Number of bookings newly marked paid
     */
    public function markManyPaid(iterable $bookings): int
    {
        $count = 0;

        foreach ($bookings as $booking) {
            if ($this->markPaid($booking)) {
                $count++;
            }
        }

        return $count;
    }

    public function markUnpaid(HealthBooking $booking): bool
    {
        $unmarked = $booking->markProfessionalUnpaid();

        if ($unmarked) {
            AppLog::info('[HealthBooking] Professional payout marked unpaid.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
            ]);
        }

        return $unmarked;
    }
}
