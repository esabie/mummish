<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class CheckProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('marketplace.product_image_max_kb', 5120);

        return [
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.$maxKb],
        ];
    }
}
