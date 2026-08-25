<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupHealthBookingReviewRequest extends FormRequest
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
            'reference' => ['required', 'string', 'max:40'],
            'patient_email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'Enter your booking reference.',
            'patient_email.required' => 'Enter the email used when booking.',
            'patient_email.email' => 'Enter a valid email address.',
        ];
    }
}
