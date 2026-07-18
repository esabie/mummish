<?php

namespace App\Support;

class ProductImageQualityResult
{
    /**
     * @param  array<int, string>  $issues
     * @param  array<int, string>  $messages
     */
    public function __construct(
        public bool $pass,
        public int $width,
        public int $height,
        public ?float $sharpnessScore,
        public ?float $brightness,
        public array $issues,
        public array $messages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pass' => $this->pass,
            'width' => $this->width,
            'height' => $this->height,
            'sharpness_score' => $this->sharpnessScore,
            'brightness' => $this->brightness,
            'issues' => $this->issues,
            'messages' => $this->messages,
        ];
    }
}
