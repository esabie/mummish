<?php

namespace App\Http\Controllers;

use App\Models\HealthBooking;
use App\Services\HealthBookingPaymentService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthBookingPaymentController extends Controller
{
    public function callback(
        Request $request,
        PaystackService $paystack,
        HealthBookingPaymentService $payments,
    ): RedirectResponse {
        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()
                ->route('health-services.index')
                ->with('error', 'Missing payment reference.');
        }

        $booking = $payments->findByPaystackReference($reference);

        if ($booking === null) {
            Log::warning('Health booking payment: callback booking not found.', [
                'reference' => $reference,
            ]);

            return redirect()
                ->route('health-services.index')
                ->with('error', 'We could not find your booking payment.');
        }

        $booking->loadMissing('professional');
        $slug = $booking->professional?->slug;

        if ($booking->isPaid() && $booking->status === 'pending') {
            return $this->successRedirect($booking, $slug);
        }

        try {
            $data = $paystack->verifyTransaction($reference);
            $payments->markPaidFromPaystack($booking, $data);

            return $this->successRedirect($booking->fresh(), $slug);
        } catch (Throwable $exception) {
            Log::warning('Health booking payment: callback verification failed.', [
                'reference' => $reference,
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('health-services.show', $slug ?: 'unavailable')
                ->with(
                    'error',
                    'Payment could not be confirmed. If you were charged, contact support with reference '.$booking->reference.'.'
                );
        }
    }

    private function successRedirect(HealthBooking $booking, ?string $slug): RedirectResponse
    {
        $date = $booking->appointment_date?->format('l, jS F Y') ?? '';
        $time = \Carbon\Carbon::parse((string) $booking->appointment_time)->format('g:i A');

        return redirect()
            ->route('health-services.show', $slug ?: 'unavailable')
            ->with(
                'success',
                "Payment received. Booking requested for {$date} at {$time}. Your reference is {$booking->reference}. We will confirm shortly."
            );
    }
}
