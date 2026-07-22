<?php

namespace Tests\Unit;

use App\Support\SupportWhatsApp;
use Tests\TestCase;

class SupportWhatsAppTest extends TestCase
{
    public function test_builds_wa_me_url_from_local_ghana_number(): void
    {
        $this->assertSame(
            'https://wa.me/233208062428',
            SupportWhatsApp::chatUrl('0208062428'),
        );
    }

    public function test_includes_prefilled_message_when_provided(): void
    {
        $url = SupportWhatsApp::chatUrl('0208062428', 'Hi Mummish, I need help with ');

        $this->assertSame(
            'https://wa.me/233208062428?text=Hi%20Mummish%2C%20I%20need%20help%20with',
            $url,
        );
    }
}
