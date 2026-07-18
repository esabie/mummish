<?php

namespace Tests\Unit;

use App\Support\PublicStorageUrl;
use Tests\TestCase;

class PublicStorageUrlTest extends TestCase
{
    public function test_it_builds_asset_url_from_storage_path(): void
    {
        $url = PublicStorageUrl::fromStored('products/3/photo.jpg');

        $this->assertStringContainsString('/storage/products/3/photo.jpg', $url);
    }

    public function test_it_normalizes_full_url_to_current_app_host(): void
    {
        $url = PublicStorageUrl::fromStored('http://localhost/storage/products/3/photo.jpg');

        $this->assertStringContainsString('/storage/products/3/photo.jpg', $url);
    }

    public function test_it_extracts_stored_path_from_url(): void
    {
        $path = PublicStorageUrl::toStoredPath('http://example.test/storage/products/1/a.jpg');

        $this->assertSame('products/1/a.jpg', $path);
    }
}
