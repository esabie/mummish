<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class VendorSettlementService
{
    public function markPaid(Order $order): bool
    {
        return $order->markVendorPaid();
    }

    /**
     * @param  Collection<int, Order>|iterable<int, Order>  $orders
     * @return int Number of orders newly marked paid
     */
    public function markManyPaid(iterable $orders): int
    {
        $count = 0;

        foreach ($orders as $order) {
            if ($order->markVendorPaid()) {
                $count++;
            }
        }

        return $count;
    }

    public function markUnpaid(Order $order): bool
    {
        return $order->markVendorUnpaid();
    }
}
