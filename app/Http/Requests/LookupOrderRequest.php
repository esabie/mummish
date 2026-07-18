<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupOrderRequest extends FormRequest
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
            'order_number' => ['required', 'string', 'max:40'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_number.required' => 'Enter your order number.',
            'customer_email.required' => 'Enter the email used at checkout.',
            'customer_email.email' => 'Enter a valid email address.',
        ];
    }
}
