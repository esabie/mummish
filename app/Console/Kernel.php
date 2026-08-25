<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ghana is GMT year-round (Africa/Accra).
        $schedule->command('vendors:remind-missing-payout-details')
            ->weeklyOn(3, '09:00')
            ->timezone('Africa/Accra')
            ->withoutOverlapping();

        $schedule->command('health-professionals:remind-missing-payout-details')
            ->weeklyOn(3, '09:15')
            ->timezone('Africa/Accra')
            ->withoutOverlapping();

        $schedule->command('health-bookings:expire-payment-holds')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
