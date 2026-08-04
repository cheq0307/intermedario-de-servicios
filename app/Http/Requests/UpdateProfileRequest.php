<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:800'],
            'city' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($this->user()->account_type->value === 'provider') {
            $rules = array_merge($rules, [
                'display_name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:1200'],
                'specialty' => ['nullable', 'string', 'max:120'],
                'service_area' => ['nullable', 'string', 'max:160'],
                'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
                'availability_status' => ['required', Rule::in(['available', 'busy', 'unavailable'])],
                'certifications' => ['nullable', 'string', 'max:1000'],
                'tools' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        return $rules;
    }
}
