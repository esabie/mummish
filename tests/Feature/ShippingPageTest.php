<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShippingPageTest extends TestCase
{
    public function test_shipping_page_renders(): void
    {
        $this->get(route('shipping'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Shipping'));
    }
}
