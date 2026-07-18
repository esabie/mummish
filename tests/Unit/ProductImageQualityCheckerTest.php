<?php

namespace Tests\Unit;

use App\Services\ProductImageQualityChecker;
use Illuminate\Http\UploadedFile;
use Tests\Support\TestProductImage;
use Tests\TestCase;

class ProductImageQualityCheckerTest extends TestCase
{
    public function test_sharp_large_image_passes(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $result = app(ProductImageQualityChecker::class)->check(
            TestProductImage::sharpJpeg('sharp.jpg', 1200, 1200)
        );

        $this->assertTrue($result->pass);
        $this->assertSame([], $result->issues);
    }

    public function test_small_image_fails_resolution_check(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $result = app(ProductImageQualityChecker::class)->check(
            TestProductImage::sharpJpeg('small.jpg', 400, 400)
        );

        $this->assertFalse($result->pass);
        $this->assertContains('resolution', $result->issues);
    }

    public function test_blurry_image_fails_sharpness_check(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $path = tempnam(sys_get_temp_dir(), 'lh-blur-').'.jpg';
        $image = imagecreatetruecolor(1200, 1200);
        $background = imagecolorallocate($image, 180, 180, 180);
        imagefilledrectangle($image, 0, 0, 1199, 1199, $background);
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        $file = new UploadedFile($path, 'blurry.jpg', 'image/jpeg', null, true);

        $result = app(ProductImageQualityChecker::class)->check($file);

        $this->assertFalse($result->pass);
        $this->assertContains('blur', $result->issues);
    }
}
