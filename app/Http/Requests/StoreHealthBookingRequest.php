<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('patient_email')) {
            $this->merge([
                'patient_email' => strtolower(trim((string) $this->input('patient_email'))),
            ]);
        }

        if ($this->has('patient_name')) {
            $this->merge([
                'patient_name' => trim((string) $this->input('patient_name')),
            ]);
        }

        if ($this->has('notes')) {
            $notes = trim((string) $this->input('notes'));
            $this->merge([
                'notes' => $notes !== '' ? $notes : null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'health_professional_service_id' => ['required', 'integer', 'exists:health_professional_services,id'],
            'visit_mode' => ['required', 'string', Rule::in(['Virtual', 'In person'])],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'patient_name' => ['required', 'string', 'max:150'],
            'patient_email' => ['required', 'email', 'max:255'],
            'patient_phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[\d\s+()-]+$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'health_professional_service_id.required' => 'Please select a service.',
            'appointment_date.required' => 'Please select a date.',
            'appointment_time.required' => 'Please select a time.',
            'patient_phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
