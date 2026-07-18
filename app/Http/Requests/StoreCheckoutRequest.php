<?php

namespace App\Http\Requests;

use App\Support\LogSanitizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreCheckoutRequest extends FormRequest
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
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.attributes' => ['nullable', 'string', 'max:120'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[\d\s+()-]+$/'],
            'shipping_address_line1' => ['required', 'string', 'max:200'],
            'shipping_address_line2' => ['nullable', 'string', 'max:200'],
            'shipping_region' => ['required', 'string', Rule::in(config('marketplace.ghana_regions', []))],
            'shipping_city' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $region = (string) $this->input('shipping_region', '');
                    $cities = config('ghana_cities.by_region.'.$region, []);

                    if ($cities === [] || ! in_array((string) $value, $cities, true)) {
                        $fail('Please select a valid city for the chosen region.');
                    }
                },
            ],
            'shipping_notes' => ['nullable', 'string', 'max:500'],
            'promo_code' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'full name',
            'customer_email' => 'email address',
            'customer_phone' => 'phone number',
            'shipping_address_line1' => 'street address',
            'shipping_address_line2' => 'apartment or suite',
            'shipping_city' => 'city',
            'shipping_region' => 'region',
            'shipping_notes' => 'delivery notes',
            'promo_code' => 'promo code',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Checkout: request validation failed.', [
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'item_count' => count($this->input('items', [])),
            'shipping' => LogSanitizer::maskShipping($this->all()),
        ]);

        throw new ValidationException($validator);
    }
}
