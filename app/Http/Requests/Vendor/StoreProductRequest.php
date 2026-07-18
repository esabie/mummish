<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Vendor\Concerns\ValidatesVendorProduct;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use ValidatesVendorProduct;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareAllowsCustomization();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->sharedProductRules(requireImages: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->sharedProductAttributes();
    }
}
