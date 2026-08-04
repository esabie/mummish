<?php

namespace App\Http\Requests;

use App\Support\EmailRoleConflict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreHealthProfessionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSignup = $this->routeIs('health-professionals.signup.store');

        $emailRules = ['required', 'email', 'max:255'];

        if ($isSignup) {
            $emailRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                $conflict = EmailRoleConflict::healthProfessionalRegistrationMessage((string) $value);

                if ($conflict !== null) {
                    $fail($conflict);
                }
            };
        } else {
            $professional = $this->route('professional');
            $emailRules[] = Rule::unique('health_professionals', 'email')->ignore($professional?->id);
            $emailRules[] = Rule::unique('users', 'email')->ignore($professional?->user_id);
        }

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'about' => ['required', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[\d\s+()-]+$/'],
            'email' => $emailRules,
            'experience_amount' => ['required', 'integer', 'min:1', 'max:100'],
            'experience_unit' => ['required', 'string', Rule::in(['days', 'months', 'years'])],
        ];

        if ($isSignup) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
            $rules['image'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];
            $rules['terms_accepted'] = ['required', 'accepted'];

            return $rules;
        }

        $rules['image'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];
        $rules['booking_note'] = ['nullable', 'string', 'max:500'];
        $rules['visit_modes'] = ['required', 'array', 'min:1'];
        $rules['visit_modes.*'] = ['required', 'string', Rule::in(['Virtual', 'In person'])];
        $rules['languages'] = ['nullable', 'array'];
        $rules['languages.*'] = ['nullable', 'string', 'max:60'];
        $rules['highlights'] = ['nullable', 'array'];
        $rules['highlights.*'] = ['nullable', 'string', 'max:255'];
        $rules['services'] = ['required', 'array', 'min:1'];
        $rules['services.*.name'] = ['required', 'string', 'max:150'];
        $rules['services.*.visit_mode'] = ['required', 'string', Rule::in(['Virtual', 'In person'])];
        $rules['services.*.price_cedis'] = ['required', 'integer', 'min:1', 'max:99999'];
        $rules['services.*.duration_minutes'] = ['nullable', 'integer', 'min:5', 'max:240'];
        $rules['availability'] = ['required', 'array', 'min:1'];
        $rules['availability.*.day_of_week'] = ['required', 'integer', 'min:0', 'max:6'];
        $rules['availability.*.start_time'] = ['required', 'date_format:H:i'];
        $rules['availability.*.end_time'] = ['required', 'date_format:H:i'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'terms_accepted.required' => 'You must agree to the Marketplace Terms & Conditions and Healthcare Professional Agreement.',
            'terms_accepted.accepted' => 'You must agree to the Marketplace Terms & Conditions and Healthcare Professional Agreement.',
        ];
    }
}
