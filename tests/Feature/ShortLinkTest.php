<?php

namespace Tests\Feature;

use App\Services\ShortLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_link_redirects_to_target_url(): void
    {
        $shortUrl = app(ShortLinkService::class)->create('https://example.com/reset?token=abc', 60);

        $this->assertMatchesRegularExpression('#/r/[a-z0-9]{8}$#', $shortUrl);

        $code = basename(parse_url($shortUrl, PHP_URL_PATH));

        $this->get('/r/'.$code)
            ->assertRedirect('https://example.com/reset?token=abc');
    }

    public function test_unknown_short_link_returns_not_found(): void
    {
        $this->get('/r/unknown1')->assertNotFound();
    }
}
