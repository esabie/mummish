<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class CourierSettlementService
{
    public function markPaid(Order $order): bool
    {
        return $order->markCourierPaid();
    }

    /**
     * @param  Collection<int, Order>|iterable<int, Order>  $orders
     * @return int Number of orders newly marked paid
     */
    public function markManyPaid(iterable $orders): int
    {
        $count = 0;

        foreach ($orders as $order) {
            if ($order->markCourierPaid()) {
                $count++;
            }
        }

        return $count;
    }

    public function markUnpaid(Order $order): bool
    {
        return $order->markCourierUnpaid();
    }
}
