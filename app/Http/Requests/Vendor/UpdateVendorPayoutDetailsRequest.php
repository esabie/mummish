<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorPayoutDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVendor() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'payment_method',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'mobile_money_provider',
            'mobile_money_name',
            'mobile_money_number',
        ];

        $data = [];

        foreach ($fields as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            $data[$field] = $trimmed === '' ? null : $trimmed;
        }

        if (isset($data['payment_method'])) {
            $data['payment_method'] = strtolower((string) $data['payment_method']);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['bank', 'mobile_money'])],
            'bank_name' => [
                'nullable',
                'string',
                'max:120',
                'required_if:payment_method,bank',
                Rule::in(config('ghana_banks.names', [])),
            ],
            'bank_account_name' => ['nullable', 'string', 'max:120', 'required_if:payment_method,bank'],
            'bank_account_number' => ['nullable', 'string', 'max:40', 'required_if:payment_method,bank'],
            'mobile_money_provider' => ['nullable', 'string', 'max:80', 'required_if:payment_method,mobile_money'],
            'mobile_money_name' => ['nullable', 'string', 'max:120', 'required_if:payment_method,mobile_money'],
            'mobile_money_number' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s+()-]+$/', 'required_if:payment_method,mobile_money'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select how you want to be paid.',
            'bank_name.required_if' => 'Please select your bank.',
            'bank_name.in' => 'Please select a bank from the list.',
            'bank_account_name.required_if' => 'Please enter the bank account name.',
            'bank_account_number.required_if' => 'Please enter the bank account number.',
            'mobile_money_provider.required_if' => 'Please select your mobile money provider.',
            'mobile_money_name.required_if' => 'Please enter the mobile money account name.',
            'mobile_money_number.required_if' => 'Please enter the mobile money number.',
            'mobile_money_number.regex' => 'Please enter a valid mobile money number.',
        ];
    }
}
