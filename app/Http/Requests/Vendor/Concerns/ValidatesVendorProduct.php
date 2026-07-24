<?php

namespace App\Http\Requests\Vendor\Concerns;

use App\Http\Requests\StoreVendorApplicationRequest;
use App\Services\ProductImageQualityChecker;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

trait ValidatesVendorProduct
{
    protected function prepareAllowsCustomization(): void
    {
        if ($this->has('allows_customization')) {
            $this->merge([
                'allows_customization' => filter_var($this->input('allows_customization'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedProductRules(bool $requireImages): array
    {
        $minImages = (int) config('marketplace.min_product_images', 3);
        $maxImages = (int) config('marketplace.max_product_images', 8);
        $maxKb = (int) config('marketplace.product_image_max_kb', 5120);

        $imageRules = $requireImages
            ? ['required', 'array', "min:{$minImages}", "max:{$maxImages}"]
            : ['nullable', 'array', 'max:'.$maxImages];

        return [
            'title' => ['required', 'string', 'max:30'],
            'category' => ['required', 'string', Rule::in(array_keys(StoreVendorApplicationRequest::categories()))],
            'brand' => ['required', 'string', 'max:120', $this->brandForCategoryRule()],
            'condition' => ['required', Rule::enum(\App\Enums\ProductCondition::class)],
            'clothing_size' => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('category'),
                    config('marketplace.categories_requiring_size', []),
                    true
                )),
                'nullable',
                'string',
                Rule::in(array_keys(config('marketplace.clothing_sizes', []))),
            ],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'compare_at_price' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:999999.99',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $salePrice = (float) $this->input('price', 0);
                    $originalPrice = (float) $value;

                    if ($originalPrice <= $salePrice) {
                        $fail('The original price must be higher than the sale price.');
                    }
                },
            ],
            'stock_quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'status' => ['required', Rule::enum(\App\Enums\ProductStatus::class)],
            'description' => ['required', 'string', 'max:10000', $this->descriptionNotEmptyRule()],
            'material_tags' => ['required', 'array', 'min:1', 'max:12'],
            'material_tags.*' => ['string', 'max:80'],
            'allows_customization' => ['required', 'boolean'],
            'images' => $imageRules,
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.$maxKb, $this->productImageQualityRule()],
            'existing_images' => ['sometimes', 'array', 'max:'.$maxImages],
            'existing_images.*' => ['string', 'max:500'],
        ];
    }

    protected function productImageQualityRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $result = app(ProductImageQualityChecker::class)->check($value);

            if (! $result->pass) {
                $fail(implode(' ', $result->messages));
            }
        };
    }

    protected function brandForCategoryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $brand = trim((string) $value);

            // "Other" is a UI sentinel — vendors must submit the actual brand name.
            if ($brand === '' || strcasecmp($brand, 'Other') === 0 || $brand === '__other__') {
                $fail('Please enter a brand name.');
            }
        };
    }

    protected function descriptionNotEmptyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $text = (string) $value;

            if ($text !== strip_tags($text)) {
                $fail('The product description must be plain text only.');

                return;
            }

            if (preg_match('/\p{N}/u', $text)) {
                $fail('The product description must not contain numbers.');

                return;
            }

            $text = trim($text);

            if ($text === '') {
                $fail('The product description is required.');

                return;
            }

            if (mb_strlen($text) < 20) {
                $fail('The product description must be at least 20 characters.');
            }
        };
    }

    protected function validateMinimumImageCount(): void
    {
        if (! $this->isMethod('PUT') && ! $this->isMethod('PATCH')) {
            return;
        }

        $minImages = (int) config('marketplace.min_product_images', 3);
        $existing = count($this->input('existing_images', []));
        $uploads = count($this->file('images', []));

        if ($existing + $uploads < $minImages) {
            $this->validator->errors()->add(
                'images',
                "Please upload at least {$minImages} product images."
            );
        }
    }

    /**
     * @return array<string, string>
     */
    protected function sharedProductAttributes(): array
    {
        return [
            'title' => 'product name',
            'sku' => 'SKU',
            'category' => 'category',
            'brand' => 'brand',
            'condition' => 'item condition',
            'clothing_size' => 'size',
            'price' => 'sale price',
            'compare_at_price' => 'original price',
            'stock_quantity' => 'stock quantity',
            'status' => 'status',
            'description' => 'product description',
            'material_tags' => 'material tags',
            'allows_customization' => 'customization option',
            'images' => 'product images',
            'images.*' => 'product image',
            'existing_images' => 'product images',
        ];
    }
}
