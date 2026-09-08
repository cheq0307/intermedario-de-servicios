<?php

namespace App\Http\Requests;

use App\Domain\Marketplace\Enums\BusinessDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => is_string($this->input('phone')) ? preg_replace('/[\s().-]+/', '', $this->input('phone')) : $this->input('phone'),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'offered_categories' => ['nullable', 'array', 'max:10'],
            'offered_categories.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/', Rule::unique('users', 'phone')->ignore($this->user()->id)],
            'bio' => ['nullable', 'string', 'max:800'],
            'community_id' => ['required', 'integer', Rule::exists('communities', 'id')->where('is_active', true)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($this->user()->vendor) {
            $rules = array_merge($rules, [
                'offered_categories' => ['required', 'array', 'min:1', 'max:10'],
                'display_name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:1200'],
                'specialty' => ['nullable', 'string', 'max:120'],
                'service_area' => ['nullable', 'string', 'max:160'],
                'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
                'availability_status' => ['required', Rule::in(['available', 'busy', 'unavailable'])],
                'business_days' => ['required', 'array', 'min:1'],
                'business_days.*' => ['required', 'distinct', Rule::in(BusinessDay::values())],
                'business_opens_at' => ['required', 'date_format:H:i'],
                'business_closes_at' => ['required', 'date_format:H:i', 'after:business_opens_at'],
                'certifications' => ['nullable', 'string', 'max:1000'],
                'tools' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'El teléfono debe contener exactamente 10 dígitos.',
            'phone.required' => 'Escribe tu número de teléfono.',
            'phone.unique' => 'Este teléfono ya está registrado en otra cuenta.',
            'community_id.required' => 'Selecciona tu ciudad y comunidad.',
            'community_id.exists' => 'La comunidad seleccionada no está disponible.',
        ];
    }
}
