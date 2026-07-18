<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

class TestProductImage
{
    public static function sharpJpeg(string $name = 'test.jpg', int $width = 1200, int $height = 1200): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lh-img-').'.jpg';
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y += 10) {
            for ($x = 0; $x < $width; $x += 10) {
                $light = (($x / 10) + ($y / 10)) % 2 === 0;
                $color = imagecolorallocate($image, $light ? 220 : 40, $light ? 220 : 40, $light ? 220 : 40);
                imagefilledrectangle(
                    $image,
                    $x,
                    $y,
                    min($x + 9, $width - 1),
                    min($y + 9, $height - 1),
                    $color
                );
            }
        }

        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }
}
