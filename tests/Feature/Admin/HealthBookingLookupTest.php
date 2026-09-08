<?php

namespace Tests\Feature\Admin;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Filament\Pages\HealthBookingLookup;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HealthBookingLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_health_booking_lookup_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/health-booking-lookup')
            ->assertOk()
            ->assertSee('Health booking lookup')
            ->assertSee('Find a health booking');
    }

    public function test_non_admin_cannot_access_health_booking_lookup(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/health-booking-lookup')
            ->assertForbidden();
    }

    public function test_admin_can_find_booking_by_reference_and_email(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $booking = $this->createBooking();

        Livewire::actingAs($admin)
            ->test(HealthBookingLookup::class)
            ->fillForm([
                'reference' => $booking->reference,
                'patient_email' => $booking->patient_email,
            ])
            ->call('lookup')
            ->assertSet('result.reference', $booking->reference)
            ->assertSet('result.patient_name', $booking->patient_name);
    }

    public function test_admin_lookup_notifies_when_not_found(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(HealthBookingLookup::class)
            ->fillForm([
                'reference' => 'HB-NONE01',
                'patient_email' => 'nobody@example.com',
            ])
            ->call('lookup')
            ->assertSet('result', null)
            ->assertNotified();
    }

    private function createBooking(): HealthBooking
    {
        $user = User::factory()->create([
            'role' => UserRole::HealthProfessional,
            'email' => 'lookup-pro-'.uniqid('', true).'@example.com',
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Lookup',
            'slug' => 'dr-lookup-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test',
            'location' => 'Accra',
            'phone' => '0240000001',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Consultation',
            'visit_mode' => 'Virtual',
            'price_cedis' => 100,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return HealthBooking::create([
            'reference' => 'HB-LOOK01',
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Ama Mensah',
            'patient_email' => 'ama.lookup@example.com',
            'patient_phone' => '0241234567',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'visit_mode' => 'Virtual',
            'status' => 'pending',
            'payment_status' => 'paid',
            'amount_cents' => 10000,
            'paid_at' => now(),
        ]);
    }
}
