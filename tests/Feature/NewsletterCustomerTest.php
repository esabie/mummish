<?php

namespace Tests\Feature;

use App\Models\NewsletterCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_join_newsletter_with_name_and_phone(): void
    {
        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'name' => 'Ama Mensah',
                'phone' => '0241234567',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('newsletterJoined');

        $this->assertDatabaseHas('newsletter_customers', [
            'name' => 'Ama Mensah',
            'phone' => '0241234567',
        ]);
    }

    public function test_guest_can_join_newsletter_with_phone_only(): void
    {
        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'name' => '',
                'phone' => '0249876543',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('newsletterJoined');

        $this->assertDatabaseHas('newsletter_customers', [
            'name' => null,
            'phone' => '0249876543',
        ]);
    }

    public function test_joining_again_with_same_phone_updates_name(): void
    {
        NewsletterCustomer::query()->create([
            'name' => 'Old Name',
            'phone' => '0241112233',
        ]);

        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'name' => 'New Name',
                'phone' => '0241112233',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseCount('newsletter_customers', 1);
        $this->assertDatabaseHas('newsletter_customers', [
            'name' => 'New Name',
            'phone' => '0241112233',
        ]);
    }

    public function test_phone_is_required(): void
    {
        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'name' => 'Ama',
                'phone' => '',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('newsletter_customers', 0);
    }
}
