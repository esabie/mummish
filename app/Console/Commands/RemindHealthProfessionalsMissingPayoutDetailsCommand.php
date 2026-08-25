<?php

namespace App\Console\Commands;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Jobs\SendHealthProfessionalMissingPayoutDetailsSms;
use App\Models\HealthProfessional;
use Illuminate\Console\Command;

class RemindHealthProfessionalsMissingPayoutDetailsCommand extends Command
{
    protected $signature = 'health-professionals:remind-missing-payout-details
                            {--sync : Send SMS immediately instead of queueing}';

    protected $description = 'SMS health professionals who have not yet saved Bank/MoMo payout details';

    public function handle(): int
    {
        $professionals = HealthProfessional::query()
            ->whereNull('payment_method')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereIn('approval_status', [
                HealthProfessionalApprovalStatus::Pending,
                HealthProfessionalApprovalStatus::Approved,
            ])
            ->orderBy('id')
            ->get(['id', 'name', 'phone']);

        if ($professionals->isEmpty()) {
            $this->info('No health professionals need a payout-details reminder.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');

        foreach ($professionals as $professional) {
            if ($sync) {
                SendHealthProfessionalMissingPayoutDetailsSms::dispatchSync($professional->id);
            } else {
                SendHealthProfessionalMissingPayoutDetailsSms::dispatch($professional->id);
            }
        }

        $this->info(($sync ? 'Sent' : 'Queued').' payout-details reminder SMS for '.$professionals->count().' health professional(s).');

        return self::SUCCESS;
    }
}
