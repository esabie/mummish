<?php

namespace Tests\Feature;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Models\HealthProfessional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthProfessionalPayoutDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_save_bank_payment_details(): void
    {
        [$user] = $this->professionalWithProfile();

        $this->actingAs($user)
            ->put(route('health-professionals.payment-details.update'), [
                'payment_method' => 'bank',
                'bank_name' => 'GCB BANK',
                'bank_account_name' => 'Dr Ama Mensah',
                'bank_account_number' => '0123456789',
            ])
            ->assertRedirect();

        $professional = $user->fresh()->healthProfessional;

        $this->assertSame('bank', $professional->payment_method);
        $this->assertSame('GCB BANK', $professional->bank_name);
        $this->assertSame('Dr Ama Mensah', $professional->bank_account_name);
        $this->assertSame('0123456789', $professional->bank_account_number);
        $this->assertNull($professional->mobile_money_provider);
        $this->assertTrue($professional->hasPaymentDetails());
    }

    public function test_professional_cannot_save_bank_name_outside_list(): void
    {
        [$user] = $this->professionalWithProfile();

        $this->actingAs($user)
            ->from(route('health-professionals.dashboard'))
            ->put(route('health-professionals.payment-details.update'), [
                'payment_method' => 'bank',
                'bank_name' => 'Not A Real Bank',
                'bank_account_name' => 'Dr Ama Mensah',
                'bank_account_number' => '0123456789',
            ])
            ->assertRedirect(route('health-professionals.dashboard'))
            ->assertSessionHasErrors('bank_name');
    }

    public function test_professional_can_save_mobile_money_details(): void
    {
        [$user] = $this->professionalWithProfile();

        $this->actingAs($user)
            ->put(route('health-professionals.payment-details.update'), [
                'payment_method' => 'mobile_money',
                'mobile_money_provider' => 'MTN MoMo',
                'mobile_money_name' => 'Dr Ama Mensah',
                'mobile_money_number' => '0241234567',
            ])
            ->assertRedirect();

        $professional = $user->fresh()->healthProfessional;

        $this->assertSame('mobile_money', $professional->payment_method);
        $this->assertSame('MTN MoMo', $professional->mobile_money_provider);
        $this->assertSame('Dr Ama Mensah', $professional->mobile_money_name);
        $this->assertSame('0241234567', $professional->mobile_money_number);
        $this->assertNull($professional->bank_name);
    }

    public function test_professional_cannot_update_payment_details_after_saving(): void
    {
        [$user, $professional] = $this->professionalWithProfile();

        $professional->update([
            'payment_method' => 'bank',
            'bank_name' => 'GCB BANK',
            'bank_account_name' => 'Dr Ama Mensah',
            'bank_account_number' => '0123456789',
        ]);

        $this->actingAs($user)
            ->from(route('health-professionals.dashboard'))
            ->put(route('health-professionals.payment-details.update'), [
                'payment_method' => 'mobile_money',
                'mobile_money_provider' => 'MTN MoMo',
                'mobile_money_name' => 'Dr Ama Mensah',
                'mobile_money_number' => '0241234567',
            ])
            ->assertRedirect(route('health-professionals.dashboard'))
            ->assertSessionHas('error');

        $professional->refresh();

        $this->assertSame('bank', $professional->payment_method);
        $this->assertSame('GCB BANK', $professional->bank_name);
        $this->assertNull($professional->mobile_money_provider);
    }

    public function test_dashboard_includes_payout_details_props(): void
    {
        [$user] = $this->professionalWithProfile();

        $this->actingAs($user)
            ->get(route('health-professionals.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HealthServices/Dashboard')
                ->has('payoutDetails')
                ->has('ghanaBanks')
                ->where('payoutDetails.payment_method', null)
                ->where('professional.has_payment_details', false));
    }

    /**
     * @return array{0: User, 1: HealthProfessional}
     */
    private function professionalWithProfile(): array
    {
        $user = User::factory()->create([
            'role' => UserRole::HealthProfessional,
            'email' => 'health-payout-'.uniqid('', true).'@example.com',
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Ama Mensah',
            'slug' => 'dr-ama-mensah-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test provider.',
            'location' => 'Accra',
            'phone' => '0241234567',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => false,
            'approval_status' => HealthProfessionalApprovalStatus::Pending,
        ]);

        return [$user, $professional];
    }
}
