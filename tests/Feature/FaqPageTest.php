<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaqPageTest extends TestCase
{
    public function test_faq_page_renders(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Faq'));
    }
}
