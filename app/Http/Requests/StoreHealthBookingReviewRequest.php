<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHealthBookingReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Choose a star rating.',
            'rating.min' => 'Choose a star rating between 1 and 5.',
            'rating.max' => 'Choose a star rating between 1 and 5.',
            'comment.max' => 'Your review can be at most 2000 characters.',
        ];
    }
}
