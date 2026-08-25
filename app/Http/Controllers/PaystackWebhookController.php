<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use App\Services\HealthBookingPaymentService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaystackService $paystack,
        CheckoutService $checkout,
        HealthBookingPaymentService $healthPayments,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        Log::info('Paystack webhook: received.', [
            'payload_length' => strlen($payload),
            'has_signature' => $signature !== null && $signature !== '',
            'content_type' => $request->header('Content-Type'),
        ]);

        if (! $paystack->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Paystack webhook: invalid signature — rejected.');

            return response('Invalid signature', 400);
        }

        $event = $request->json('event');
        $data = $request->json('data');

        Log::debug('Paystack webhook: payload parsed.', [
            'event' => $event,
            'data_keys' => is_array($data) ? array_keys($data) : null,
        ]);

        if ($event !== 'charge.success' || ! is_array($data)) {
            Log::info('Paystack webhook: ignored — unhandled event.', [
                'event' => $event,
            ]);

            return response('Ignored', 200);
        }

        $reference = (string) ($data['reference'] ?? '');

        Log::info('Paystack webhook: processing charge.success.', [
            'reference' => $reference !== '' ? $reference : null,
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'] ?? null,
            'channel' => $data['channel'] ?? null,
        ]);

        if ($reference === '') {
            Log::warning('Paystack webhook: charge.success missing reference.');

            return response('Missing reference', 400);
        }

        $order = $checkout->findOrderByReference($reference);

        if ($order !== null) {
            Log::debug('Paystack webhook: order resolved.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status->value,
                'expected_amount' => $order->total_cents,
            ]);

            try {
                $checkout->markOrderPaidFromPaystack($order, $data);

                Log::info('Paystack webhook: order marked paid.', [
                    'order_id' => $order->id,
                    'reference' => $reference,
                ]);
            } catch (Throwable $exception) {
                Log::error('Paystack webhook: failed to mark order paid.', [
                    'reference' => $reference,
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                    'exception' => $exception::class,
                ]);

                return response('Processing failed', 500);
            }

            return response('OK', 200);
        }

        $booking = $healthPayments->findByPaystackReference($reference);

        if ($booking === null) {
            Log::warning('Paystack webhook: order/booking not found.', [
                'reference' => $reference,
            ]);

            return response('Not found', 404);
        }

        try {
            $healthPayments->markPaidFromPaystack($booking, $data);

            Log::info('Paystack webhook: health booking marked paid.', [
                'booking_id' => $booking->id,
                'reference' => $reference,
            ]);
        } catch (Throwable $exception) {
            Log::error('Paystack webhook: failed to mark health booking paid.', [
                'reference' => $reference,
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }
}
