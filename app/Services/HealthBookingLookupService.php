<?php

namespace App\Services;

use App\Models\HealthBooking;
use Illuminate\Database\Eloquent\Builder;

class HealthBookingLookupService
{
    public function findByReferenceAndEmail(
        string $reference,
        string $email,
        ?int $professionalId = null,
    ): ?HealthBooking {
        $reference = strtoupper(trim($reference));
        $email = strtolower(trim($email));

        if ($reference === '' || $email === '') {
            return null;
        }

        return HealthBooking::query()
            ->with(['professional', 'service'])
            ->whereRaw('UPPER(reference) = ?', [$reference])
            ->whereRaw('LOWER(patient_email) = ?', [$email])
            ->when(
                $professionalId !== null,
                fn (Builder $query) => $query->where('health_professional_id', $professionalId)
            )
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(HealthBooking $booking): array
    {
        return $this->toArray($booking, includeAdminLinks: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toProfessionalArray(HealthBooking $booking): array
    {
        return $this->toArray($booking, includeAdminLinks: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(HealthBooking $booking, bool $includeAdminLinks): array
    {
        $date = $booking->appointment_date;
        $timeLabel = $booking->appointment_time
            ? \Carbon\Carbon::parse((string) $booking->appointment_time)->format('g:i A')
            : null;

        $payload = [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'status_label' => ucfirst(str_replace('_', ' ', (string) $booking->status)),
            'payment_status' => $booking->payment_status,
            'payment_status_label' => ucfirst(str_replace('_', ' ', (string) $booking->payment_status)),
            'patient_name' => $booking->patient_name,
            'patient_email' => $booking->patient_email,
            'patient_phone' => $booking->patient_phone,
            'visit_mode' => $booking->visit_mode,
            'service_name' => $booking->service?->name,
            'professional_name' => $booking->professional?->name,
            'professional_id' => $booking->health_professional_id,
            'appointment_date' => optional($date)?->toDateString(),
            'appointment_date_label' => optional($date)?->format('D, j M Y'),
            'appointment_time' => $timeLabel,
            'notes' => $booking->notes,
            'cancellation_reason' => $booking->cancellation_reason,
            'amount_label' => 'GHS '.number_format(((int) $booking->amount_cents) / 100, 2),
            'paystack_reference' => $booking->paystack_reference,
            'paystack_transaction_id' => $booking->paystack_transaction_id,
            'paid_at' => optional($booking->paid_at)?->format('M j, Y g:i A'),
            'confirmed_at' => optional($booking->confirmed_at)?->format('M j, Y g:i A'),
            'meeting_url' => $booking->meeting_url,
            'meeting_location' => $booking->meeting_location,
            'meeting_whatsapp' => $booking->meeting_whatsapp,
            'logistics_notes' => $booking->logistics_notes,
            'join_url' => $booking->sessionJoinUrl(),
            'can_join_session' => $booking->canJoinSession(),
            'can_add_meeting_url' => $booking->isVirtual()
                && in_array($booking->status, ['confirmed', 'completed'], true)
                && ! filled($booking->meeting_url),
        ];

        if ($includeAdminLinks) {
            $payload['admin_booking_url'] = \App\Filament\Resources\HealthBookingResource::getUrl('view', [
                'record' => $booking,
            ]);
            $payload['admin_professional_url'] = $booking->professional
                ? \App\Filament\Resources\HealthProfessionalResource::getUrl('view', [
                    'record' => $booking->professional,
                ])
                : null;
        }

        return $payload;
    }
}
