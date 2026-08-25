<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class HealthBooking extends Model
{
    protected $fillable = [
        'reference',
        'health_professional_id',
        'health_professional_service_id',
        'patient_name',
        'patient_email',
        'patient_phone',
        'appointment_date',
        'appointment_time',
        'visit_mode',
        'status',
        'notes',
        'amount_cents',
        'commission_cents',
        'professional_payout_cents',
        'payment_status',
        'paystack_reference',
        'paystack_transaction_id',
        'paid_at',
        'payment_expires_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'professional_paid_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'amount_cents' => 'integer',
        'commission_cents' => 'integer',
        'professional_payout_cents' => 'integer',
        'paid_at' => 'datetime',
        'payment_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'professional_paid_at' => 'datetime',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(HealthProfessional::class, 'health_professional_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HealthProfessionalService::class, 'health_professional_service_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(HealthProfessionalReview::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isProfessionalPaid(): bool
    {
        return $this->professional_paid_at !== null;
    }

    /**
     * Payout is due after the consultation is completed and the patient has paid.
     */
    public function professionalPayoutIsDue(): bool
    {
        return $this->isPaid()
            && $this->status === 'completed'
            && ! $this->isProfessionalPaid()
            && (int) $this->professional_payout_cents > 0;
    }

    public function markProfessionalPaid(): bool
    {
        if (! $this->professionalPayoutIsDue()) {
            return false;
        }

        $this->forceFill(['professional_paid_at' => now()])->save();

        return true;
    }

    public function markProfessionalUnpaid(): bool
    {
        if (! $this->isPaid() || ! $this->isProfessionalPaid()) {
            return false;
        }

        $this->forceFill(['professional_paid_at' => null])->save();

        return true;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'awaiting_payment'
            && $this->payment_expires_at !== null
            && $this->payment_expires_at->isFuture();
    }

    /**
     * Bookings that currently reserve a calendar slot.
     */
    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereIn('status', ['pending', 'confirmed'])
                ->orWhere(function (Builder $awaiting) {
                    $awaiting->where('status', 'awaiting_payment')
                        ->where('payment_expires_at', '>', now());
                });
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'HB-'.strtoupper(Str::random(6));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public static function paymentHoldMinutes(): int
    {
        return max(5, (int) config('marketplace.health_booking_payment_hold_minutes', 20));
    }
}
