<?php

namespace App\Services;

use App\Jobs\SendHealthBookingProfessionalAlertSms;
use App\Jobs\SendHealthBookingRequestedSms;
use App\Models\HealthBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HealthBookingPaymentService
{
    public function __construct(
        private PaystackService $paystack,
        private VendorEarningsService $earnings,
    ) {}

    /**
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function startPaystackPayment(HealthBooking $booking): array
    {
        if ($booking->amount_cents < 1) {
            throw new RuntimeException('Booking amount is invalid.');
        }

        $reference = $booking->paystack_reference ?: $booking->reference;

        if ($booking->paystack_reference !== $reference) {
            $booking->forceFill(['paystack_reference' => $reference])->save();
        }

        return $this->paystack->initializeTransaction(
            email: (string) $booking->patient_email,
            amountCents: (int) $booking->amount_cents,
            reference: $reference,
            callbackUrl: route('health-services.bookings.callback'),
            metadata: [
                'type' => 'health_booking',
                'booking_id' => $booking->id,
                'booking_reference' => $booking->reference,
                'health_professional_id' => $booking->health_professional_id,
            ],
        );
    }

    public function findByPaystackReference(string $reference): ?HealthBooking
    {
        return HealthBooking::query()
            ->where(function ($query) use ($reference) {
                $query->where('paystack_reference', $reference)
                    ->orWhere('reference', $reference);
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $paystackData
     */
    public function markPaidFromPaystack(HealthBooking $booking, array $paystackData): HealthBooking
    {
        if ($booking->isPaid() && $booking->status === 'pending') {
            return $booking;
        }

        $status = (string) ($paystackData['status'] ?? '');
        $amount = (int) ($paystackData['amount'] ?? 0);

        if ($status !== 'success') {
            throw new RuntimeException('Paystack payment was not successful.');
        }

        if ($amount !== (int) $booking->amount_cents) {
            Log::warning('Health booking payment: amount mismatch.', [
                'booking_id' => $booking->id,
                'expected' => $booking->amount_cents,
                'received' => $amount,
            ]);

            throw new RuntimeException('Paystack payment amount does not match booking total.');
        }

        if ($booking->status === 'cancelled' || $booking->status === 'completed') {
            throw new RuntimeException('This booking can no longer accept payment.');
        }

        // Another paid hold may have taken the slot while this payment was in flight.
        if ($this->slotTakenByAnother($booking)) {
            throw new RuntimeException('That time slot was taken while payment was processing.');
        }

        $wasAwaiting = $booking->status === 'awaiting_payment';

        DB::transaction(function () use ($booking, $paystackData) {
            $booking->refresh();

            $booking->forceFill([
                'status' => 'pending',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_expires_at' => null,
                'paystack_transaction_id' => (string) ($paystackData['id'] ?? $paystackData['transaction_id'] ?? $booking->paystack_transaction_id),
            ])->save();
        });

        if ($wasAwaiting) {
            SendHealthBookingRequestedSms::dispatch($booking->id);
            SendHealthBookingProfessionalAlertSms::dispatch($booking->id);
        }

        Log::info('Health booking payment: marked paid.', [
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'amount_cents' => $booking->amount_cents,
        ]);

        return $booking->fresh();
    }

    /**
     * @return array{gross_cents: int, commission_cents: int, payout_cents: int}
     */
    public function splitForAmount(int $amountCents): array
    {
        $split = $this->earnings->splitAmount($amountCents);

        return [
            'gross_cents' => $split['gross_cents'],
            'commission_cents' => $split['commission_cents'],
            'payout_cents' => $split['payout_cents'],
        ];
    }

    public function expireUnpaidHolds(): int
    {
        $expired = HealthBooking::query()
            ->where('status', 'awaiting_payment')
            ->where(function ($query) {
                $query->whereNull('payment_expires_at')
                    ->orWhere('payment_expires_at', '<=', now());
            })
            ->get();

        foreach ($expired as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'expired',
                'cancelled_at' => now(),
                'cancellation_reason' => $booking->cancellation_reason ?: 'Payment was not completed in time.',
            ]);
        }

        return $expired->count();
    }

    private function slotTakenByAnother(HealthBooking $booking): bool
    {
        $appointmentTime = Carbon::parse((string) $booking->appointment_time)->format('H:i');

        return HealthBooking::query()
            ->where('health_professional_id', $booking->health_professional_id)
            ->whereKeyNot($booking->id)
            ->holdingSlot()
            ->whereDate('appointment_date', Carbon::parse($booking->appointment_date)->toDateString())
            ->get()
            ->contains(function (HealthBooking $existing) use ($appointmentTime) {
                return Carbon::parse((string) $existing->appointment_time)->format('H:i') === $appointmentTime;
            });
    }
}
