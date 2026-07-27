<?php

namespace Tests\Feature;

use App\Enums\VendorApplicationStatus;
use App\Jobs\SendVendorMissingPayoutDetailsSms;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RemindVendorsMissingPayoutDetailsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_queues_sms_only_for_vendors_missing_payment_details(): void
    {
        Bus::fake();

        $missing = $this->makeApplication('Ama', '0241111111', VendorApplicationStatus::Approved);
        $this->makeApplication('Kofi', '0242222222', VendorApplicationStatus::Pending);
        $this->makeApplication('Efua', '0243333333', VendorApplicationStatus::Approved, [
            'payment_method' => 'bank',
            'bank_name' => 'GCB BANK',
            'bank_account_name' => 'Efua Mensah',
            'bank_account_number' => '1234567890',
        ]);
        $this->makeApplication('Yaw', '0244444444', VendorApplicationStatus::Closed);
        $this->makeApplication('Abena', '0245555555', VendorApplicationStatus::Rejected);

        $this->artisan('vendors:remind-missing-payout-details')
            ->expectsOutputToContain('Queued payout-details reminder SMS for 2 vendor(s).')
            ->assertSuccessful();

        Bus::assertDispatched(SendVendorMissingPayoutDetailsSms::class, 2);
        Bus::assertDispatched(
            SendVendorMissingPayoutDetailsSms::class,
            fn (SendVendorMissingPayoutDetailsSms $job) => $job->vendorApplicationId === $missing->id
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeApplication(
        string $firstName,
        string $phone,
        VendorApplicationStatus $status,
        array $overrides = [],
    ): VendorApplication {
        $user = User::factory()->create();

        return VendorApplication::create(array_merge([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => 'Mensah',
            'shop_name' => "{$firstName}'s Shop",
            'business_email' => $user->email,
            'phone' => $phone,
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => $status,
        ], $overrides));
    }
}
