<?php

namespace App\Services;

use App\Filament\Resources\HealthBookingResource;
use App\Filament\Resources\OrderResource;
use App\Models\HealthBooking;
use App\Models\Order;
use Carbon\Carbon;
use Throwable;

class PaymentLookupService
{
    public function __construct(
        private PaystackService $paystack,
    ) {}

    /**
     * Look up local payment records and optionally verify against Paystack.
     *
     * @return array{
     *     query: string,
     *     matches: list<array<string, mixed>>,
     *     paystack: array<string, mixed>|null,
     *     paystack_error: string|null
     * }
     */
    public function lookup(string $query, bool $verifyWithPaystack = true): array
    {
        $query = trim($query);

        $matches = [];

        if ($query !== '') {
            $matches = array_merge(
                $this->findOrders($query),
                $this->findHealthBookings($query),
            );
        }

        $paystack = null;
        $paystackError = null;

        if ($verifyWithPaystack && $query !== '') {
            try {
                $paystack = $this->formatPaystackPayload(
                    $this->paystack->verifyTransaction($query)
                );
            } catch (Throwable $exception) {
                $paystackError = $exception->getMessage();
            }
        }

        return [
            'query' => $query,
            'matches' => $matches,
            'paystack' => $paystack,
            'paystack_error' => $paystackError,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findOrders(string $query): array
    {
        return Order::query()
            ->where(function ($builder) use ($query) {
                $builder->where('order_number', $query)
                    ->orWhere('paystack_reference', $query)
                    ->orWhere('paystack_transaction_id', $query);
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (Order $order) {
                $paymentStatus = $order->payment_status?->value ?? 'unknown';

                return [
                    'type' => 'shop_order',
                    'type_label' => 'Shop order',
                    'id' => $order->id,
                    'reference' => $order->order_number,
                    'paystack_reference' => $order->paystack_reference,
                    'paystack_transaction_id' => $order->paystack_transaction_id,
                    'local_status' => $paymentStatus,
                    'local_status_label' => $order->payment_status?->label() ?? ucfirst($paymentStatus),
                    'lifecycle_status' => $order->status?->value,
                    'lifecycle_status_label' => $order->status?->label() ?? null,
                    'amount_cents' => (int) $order->total_cents,
                    'amount_label' => 'GHS '.number_format(((int) $order->total_cents) / 100, 2),
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'paid_at' => $order->paid_at?->toDateTimeString(),
                    'created_at' => $order->created_at?->toDateTimeString(),
                    'admin_url' => OrderResource::getUrl('view', ['record' => $order]),
                    'mismatch_hints' => [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findHealthBookings(string $query): array
    {
        return HealthBooking::query()
            ->with(['professional:id,name', 'service:id,name'])
            ->where(function ($builder) use ($query) {
                $builder->where('reference', $query)
                    ->orWhere('paystack_reference', $query)
                    ->orWhere('paystack_transaction_id', $query);
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (HealthBooking $booking) {
                $paymentStatus = (string) ($booking->payment_status ?? 'unknown');

                return [
                    'type' => 'health_booking',
                    'type_label' => 'Health booking',
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'paystack_reference' => $booking->paystack_reference,
                    'paystack_transaction_id' => $booking->paystack_transaction_id,
                    'local_status' => $paymentStatus,
                    'local_status_label' => ucfirst(str_replace('_', ' ', $paymentStatus)),
                    'lifecycle_status' => $booking->status,
                    'lifecycle_status_label' => ucfirst(str_replace('_', ' ', (string) $booking->status)),
                    'amount_cents' => (int) $booking->amount_cents,
                    'amount_label' => 'GHS '.number_format(((int) $booking->amount_cents) / 100, 2),
                    'customer_name' => $booking->patient_name,
                    'customer_email' => $booking->patient_email,
                    'customer_phone' => $booking->patient_phone,
                    'provider_name' => $booking->professional?->name,
                    'service_name' => $booking->service?->name,
                    'paid_at' => $booking->paid_at?->toDateTimeString(),
                    'created_at' => $booking->created_at?->toDateTimeString(),
                    'admin_url' => HealthBookingResource::getUrl('view', ['record' => $booking]),
                    'mismatch_hints' => [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatPaystackPayload(array $data): array
    {
        $amount = isset($data['amount']) ? (int) $data['amount'] : null;
        $paidAt = $data['paid_at'] ?? null;
        $paidAtLabel = null;

        if (is_string($paidAt) && $paidAt !== '') {
            try {
                $paidAtLabel = Carbon::parse($paidAt)->toDateTimeString();
            } catch (Throwable) {
                $paidAtLabel = $paidAt;
            }
        }

        $customer = is_array($data['customer'] ?? null) ? $data['customer'] : [];

        return [
            'status' => (string) ($data['status'] ?? ''),
            'status_label' => ucfirst((string) ($data['status'] ?? 'unknown')),
            'reference' => (string) ($data['reference'] ?? ''),
            'transaction_id' => isset($data['id']) ? (string) $data['id'] : null,
            'amount_cents' => $amount,
            'amount_label' => $amount !== null
                ? 'GHS '.number_format($amount / 100, 2)
                : null,
            'currency' => (string) ($data['currency'] ?? 'GHS'),
            'channel' => (string) ($data['channel'] ?? ''),
            'gateway_response' => (string) ($data['gateway_response'] ?? ''),
            'paid_at' => $paidAtLabel,
            'customer_email' => (string) ($customer['email'] ?? ''),
            'metadata' => is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        ];
    }
}
