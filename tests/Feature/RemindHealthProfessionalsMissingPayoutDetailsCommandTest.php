<?php

namespace Tests\Feature;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Jobs\SendHealthProfessionalMissingPayoutDetailsSms;
use App\Models\HealthProfessional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RemindHealthProfessionalsMissingPayoutDetailsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_reminders_for_professionals_missing_payout_details(): void
    {
        Bus::fake();

        $missing = $this->createProfessional('Dr Missing Details');
        $this->createProfessional('Dr Has Details', [
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'MTN MoMo',
            'mobile_money_name' => 'Dr Has Details',
            'mobile_money_number' => '0241111111',
        ]);

        $this->artisan('health-professionals:remind-missing-payout-details')
            ->expectsOutput('Queued payout-details reminder SMS for 1 health professional(s).')
            ->assertSuccessful();

        Bus::assertDispatched(
            SendHealthProfessionalMissingPayoutDetailsSms::class,
            fn (SendHealthProfessionalMissingPayoutDetailsSms $job) => $job->healthProfessionalId === $missing->id,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProfessional(string $name, array $overrides = []): HealthProfessional
    {
        $user = User::factory()->create([
            'role' => UserRole::HealthProfessional,
            'email' => 'health-cmd-'.uniqid('', true).'@example.com',
        ]);

        return HealthProfessional::create(array_merge([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => 'slug-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ], $overrides));
    }
}
