<?php

namespace App\Enums;

enum VendorFulfillmentStatus: string
{
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case ReceivedAtWarehouse = 'received_at_warehouse';
    case ShippedToCustomer = 'shipped_to_customer';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::ReadyForPickup => 'Ready for pickup',
            self::PickedUp => 'Picked up',
            self::ReceivedAtWarehouse => 'At warehouse',
            self::ShippedToCustomer => 'Shipped to customer',
            self::Delivered => 'Delivered',
        };
    }

    /**
     * Escrow releases only after the customer (or admin) confirms delivery.
     */
    public function releasesEscrow(): bool
    {
        return $this === self::Delivered;
    }
}
