<?php

namespace App\Http\Requests;

use App\Models\VendorReferrer;
use App\Support\EmailRoleConflict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreVendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('referral_code')) {
            $value = strtoupper(trim((string) $this->input('referral_code')));
            $this->merge(['referral_code' => $value !== '' ? $value : null]);
        }

        if (! $this->has('ghana_card_id')) {
            return;
        }

        $value = strtoupper(str_replace(' ', '', (string) $this->input('ghana_card_id')));

        if (preg_match('/^GHA(\d{9})(\d)$/', $value, $matches)) {
            $value = "GHA-{$matches[1]}-{$matches[2]}";
        }

        $this->merge(['ghana_card_id' => $value]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'shop_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[\d\s+()-]+$/'],
            'ghana_card_id' => ['required', 'string', 'regex:/^GHA-\d{9}-\d$/', 'unique:vendor_applications,ghana_card_id'],
            'category' => ['required', 'string', Rule::in(array_keys(self::categories()))],
            'referral_code' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = VendorReferrer::query()
                        ->active()
                        ->where('code', strtoupper(trim((string) $value)))
                        ->exists();

                    if (! $exists) {
                        $fail('This referral code is not valid.');
                    }
                },
            ],
            'terms_accepted' => ['required', 'accepted'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];

        if ($this->user()) {
            return $rules;
        }

        $rules['email'] = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $conflict = EmailRoleConflict::vendorRegistrationMessage((string) $value);

                if ($conflict !== null) {
                    $fail($conflict);
                }
            },
        ];
        $rules['password'] = ['required', 'confirmed', Password::defaults()];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
            'ghana_card_id.required' => 'Please enter your Ghana Card ID number.',
            'ghana_card_id.regex' => 'Ghana Card ID must be in the format GHA-123456789-0.',
            'ghana_card_id.unique' => 'This Ghana Card ID has already been used on a vendor application.',
            'category.required' => 'Please select what you sell.',
            'terms_accepted.required' => 'You must agree to the Marketplace Terms & Conditions and Vendor Agreement.',
            'terms_accepted.accepted' => 'You must agree to the Marketplace Terms & Conditions and Vendor Agreement.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'shop_name' => 'shop name',
            'email' => 'email address',
            'password' => 'password',
            'phone' => 'phone number',
            'ghana_card_id' => 'Ghana Card ID number',
            'category' => 'category',
            'referral_code' => 'referral code',
            'logo' => 'shop logo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return config('marketplace.categories', []);
    }
}
