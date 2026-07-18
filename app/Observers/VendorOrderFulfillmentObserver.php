<?php

namespace App\Observers;

use App\Enums\VendorFulfillmentStatus;
use App\Models\VendorOrderFulfillment;
use App\Notifications\VendorFulfillmentStatusNotification;

class VendorOrderFulfillmentObserver
{
    public function updated(VendorOrderFulfillment $fulfillment): void
    {
        if (! $fulfillment->wasChanged('status')) {
            return;
        }

        // Vendors mark ready-for-pickup themselves — no need to notify them.
        if ($fulfillment->status === VendorFulfillmentStatus::ReadyForPickup) {
            return;
        }

        $fulfillment->loadMissing(['order', 'vendor']);

        if ($fulfillment->vendor === null) {
            return;
        }

        $fulfillment->vendor->notify(new VendorFulfillmentStatusNotification($fulfillment));
    }
}
