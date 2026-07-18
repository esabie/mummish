<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Vendor\Concerns\ValidatesVendorProduct;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use ValidatesVendorProduct;

    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $product->user_id === $this->user()->id;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareAllowsCustomization();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateMinimumImageCount();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->sharedProductRules(requireImages: false);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->sharedProductAttributes();
    }
}
