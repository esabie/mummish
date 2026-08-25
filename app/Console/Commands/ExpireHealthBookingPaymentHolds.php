<?php

namespace App\Console\Commands;

use App\Services\HealthBookingPaymentService;
use Illuminate\Console\Command;

class ExpireHealthBookingPaymentHolds extends Command
{
    protected $signature = 'health-bookings:expire-payment-holds';

    protected $description = 'Cancel health bookings that did not complete Paystack payment before the hold expired';

    public function handle(HealthBookingPaymentService $payments): int
    {
        $count = $payments->expireUnpaidHolds();

        $this->info("Expired {$count} unpaid health booking hold(s).");

        return self::SUCCESS;
    }
}
