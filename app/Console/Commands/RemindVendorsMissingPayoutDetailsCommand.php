<?php

namespace App\Console\Commands;

use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorMissingPayoutDetailsSms;
use App\Models\VendorApplication;
use Illuminate\Console\Command;

class RemindVendorsMissingPayoutDetailsCommand extends Command
{
    protected $signature = 'vendors:remind-missing-payout-details
                            {--sync : Send SMS immediately instead of queueing}';

    protected $description = 'SMS vendors who have not yet saved Bank/MoMo payout details';

    public function handle(): int
    {
        $applications = VendorApplication::query()
            ->whereNull('payment_method')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereIn('status', [
                VendorApplicationStatus::Pending,
                VendorApplicationStatus::Approved,
            ])
            ->orderBy('id')
            ->get(['id', 'first_name', 'shop_name', 'phone']);

        if ($applications->isEmpty()) {
            $this->info('No vendors need a payout-details reminder.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');

        foreach ($applications as $application) {
            if ($sync) {
                SendVendorMissingPayoutDetailsSms::dispatchSync($application->id);
            } else {
                SendVendorMissingPayoutDetailsSms::dispatch($application->id);
            }
        }

        $this->info(($sync ? 'Sent' : 'Queued').' payout-details reminder SMS for '.$applications->count().' vendor(s).');

        return self::SUCCESS;
    }
}
