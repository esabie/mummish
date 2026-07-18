<?php

namespace App\Models;

use App\Enums\VendorFulfillmentStatus;
use App\Jobs\SendOrderDeliveredSms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOrderFulfillment extends Model
{
    protected $fillable = [
        'order_id',
        'vendor_user_id',
        'status',
        'ready_for_pickup_at',
        'picked_up_at',
        'received_at_warehouse_at',
        'shipped_to_customer_at',
        'delivered_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'status' => VendorFulfillmentStatus::class,
        'ready_for_pickup_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'received_at_warehouse_at' => 'datetime',
        'shipped_to_customer_at' => 'datetime',
        'delivered_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function isReadyForPickup(): bool
    {
        return $this->status === VendorFulfillmentStatus::ReadyForPickup;
    }

    public function isShippedToCustomer(): bool
    {
        return $this->status === VendorFulfillmentStatus::ShippedToCustomer;
    }

    public function isDelivered(): bool
    {
        return $this->status === VendorFulfillmentStatus::Delivered;
    }

    public function releasesEscrow(): bool
    {
        return $this->status?->releasesEscrow() ?? false;
    }

    public function markDelivered(): bool
    {
        if ($this->isDelivered()) {
            return false;
        }

        if ($this->status !== VendorFulfillmentStatus::ShippedToCustomer) {
            return false;
        }

        $this->update([
            'status' => VendorFulfillmentStatus::Delivered,
            'delivered_at' => now(),
        ]);

        SendOrderDeliveredSms::dispatch($this->order_id)->afterCommit();

        return true;
    }
}
