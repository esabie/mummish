<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromoCostBearer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'paystack_reference',
        'paystack_transaction_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_region',
        'shipping_notes',
        'subtotal_cents',
        'shipping_cents',
        'promo_code',
        'discount_cents',
        'promo_cost_bearer',
        'total_cents',
        'currency',
        'paid_at',
        'courier_paid_at',
        'vendor_paid_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'promo_cost_bearer' => PromoCostBearer::class,
        'subtotal_cents' => 'integer',
        'shipping_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'paid_at' => 'datetime',
        'courier_paid_at' => 'datetime',
        'vendor_paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorFulfillments(): HasMany
    {
        return $this->hasMany(VendorOrderFulfillment::class);
    }

    public function formattedTotal(): string
    {
        return 'GHS '.number_format($this->total_cents / 100, 2);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function hasCourierFee(): bool
    {
        return (int) $this->shipping_cents > 0;
    }

    public function isCourierPaid(): bool
    {
        return $this->courier_paid_at !== null;
    }

    public function courierFeeIsDue(): bool
    {
        return $this->isPaid() && $this->hasCourierFee() && ! $this->isCourierPaid();
    }

    public function markCourierPaid(): bool
    {
        if (! $this->courierFeeIsDue()) {
            return false;
        }

        $this->update(['courier_paid_at' => now()]);

        return true;
    }

    public function markCourierUnpaid(): bool
    {
        if (! $this->isCourierPaid()) {
            return false;
        }

        $this->update(['courier_paid_at' => null]);

        return true;
    }

    public function isVendorPaid(): bool
    {
        return $this->vendor_paid_at !== null;
    }

    public function hasReleasedEscrow(): bool
    {
        $fulfillments = $this->relationLoaded('vendorFulfillments')
            ? $this->vendorFulfillments
            : $this->vendorFulfillments()->get();

        return $fulfillments->contains(fn (VendorOrderFulfillment $fulfillment) => $fulfillment->releasesEscrow());
    }

    public function vendorPayoutIsDue(): bool
    {
        return $this->isPaid() && $this->hasReleasedEscrow() && ! $this->isVendorPaid();
    }

    public function markVendorPaid(): bool
    {
        if (! $this->vendorPayoutIsDue()) {
            return false;
        }

        $this->update(['vendor_paid_at' => now()]);

        return true;
    }

    public function markVendorUnpaid(): bool
    {
        if (! $this->isVendorPaid()) {
            return false;
        }

        $this->update(['vendor_paid_at' => null]);

        return true;
    }
}
