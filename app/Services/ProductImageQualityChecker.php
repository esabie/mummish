<?php

namespace App\Services;

use App\Support\ProductImageQualityResult;
use GdImage;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProductImageQualityChecker
{
    public function check(UploadedFile $file): ProductImageQualityResult
    {
        $image = $this->loadImage($file);

        if ($image === null) {
            return new ProductImageQualityResult(
                pass: false,
                width: 0,
                height: 0,
                sharpnessScore: null,
                brightness: null,
                issues: ['unreadable'],
                messages: ['We could not read this image. Try JPEG, PNG, or WebP.'],
            );
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $minWidth = (int) config('marketplace.product_image_min_width', 500);
        $minHeight = (int) config('marketplace.product_image_min_height', 500);
        $minSharpness = (float) config('marketplace.product_image_min_sharpness', 20);

        $issues = [];
        $messages = [];

        if ($width < $minWidth || $height < $minHeight) {
            $issues[] = 'resolution';
            $messages[] = "Image must be at least {$minWidth}×{$minHeight} pixels (yours is {$width}×{$height}).";
        }

        $analysisImage = $this->resizeForAnalysis($image, 600);
        $sharpness = $this->laplacianVariance($analysisImage);

        if ($sharpness < $minSharpness) {
            $issues[] = 'blur';
            $messages[] = 'Photo looks very blurry. Hold steady and tap to focus on the product before retaking.';
        }

        imagedestroy($image);
        if ($analysisImage !== $image) {
            imagedestroy($analysisImage);
        }

        return new ProductImageQualityResult(
            pass: $issues === [],
            width: $width,
            height: $height,
            sharpnessScore: round($sharpness, 2),
            brightness: null,
            issues: $issues,
            messages: $messages,
        );
    }

    private function loadImage(UploadedFile $file): ?GdImage
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for product image quality checks.');
        }

        $path = $file->getRealPath();

        if ($path === false) {
            return null;
        }

        $mime = $file->getMimeType() ?? '';

        return match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => @imagecreatefromjpeg($path) ?: null,
            str_contains($mime, 'png') => @imagecreatefrompng($path) ?: null,
            str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }

    private function resizeForAnalysis(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= $maxDimension) {
            return $image;
        }

        $scale = $maxDimension / $longest;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $resized;
    }

    private function laplacianVariance(GdImage $image): float
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 3 || $height < 3) {
            return 0.0;
        }

        $step = max(1, (int) floor(max($width, $height) / 300));
        $sum = 0.0;
        $sumSq = 0.0;
        $count = 0;

        for ($y = 1; $y < $height - 1; $y += $step) {
            for ($x = 1; $x < $width - 1; $x += $step) {
                $center = $this->grayscaleAt($image, $x, $y);
                $laplacian = (4 * $center)
                    - $this->grayscaleAt($image, $x - 1, $y)
                    - $this->grayscaleAt($image, $x + 1, $y)
                    - $this->grayscaleAt($image, $x, $y - 1)
                    - $this->grayscaleAt($image, $x, $y + 1);

                $sum += $laplacian;
                $sumSq += $laplacian * $laplacian;
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        $mean = $sum / $count;

        return ($sumSq / $count) - ($mean * $mean);
    }

    private function grayscaleAt(GdImage $image, int $x, int $y): float
    {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }
}
