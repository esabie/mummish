<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactPageTest extends TestCase
{
    public function test_contact_page_renders(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Contact')
                ->where('supportPhone', '0208062428')
                ->where('supportWhatsAppUrl', 'https://wa.me/233208062428?text=Hi%20Mummish%2C%20I%20need%20help%20with'));
    }
}
