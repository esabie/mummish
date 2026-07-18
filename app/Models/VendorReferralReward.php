<?php

namespace App\Models;

use App\Enums\VendorReferralRewardStatus;
use App\Enums\VendorReferralRewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorReferralReward extends Model
{
    protected $fillable = [
        'vendor_referrer_id',
        'vendor_application_id',
        'order_id',
        'order_item_id',
        'type',
        'amount_cents',
        'description',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'type' => VendorReferralRewardType::class,
        'status' => VendorReferralRewardStatus::class,
        'amount_cents' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(VendorReferrer::class, 'vendor_referrer_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VendorApplication::class, 'vendor_application_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function formattedAmount(): string
    {
        return 'GHS '.number_format($this->amount_cents / 100, 2);
    }
}
