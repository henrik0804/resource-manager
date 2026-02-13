<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQualificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('qualification')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'resource_type_id' => ['sometimes', 'nullable', 'integer', 'exists:resource_types,id', 'prohibits:resource_type'],
            'resource_type' => ['sometimes', 'array', 'prohibits:resource_type_id'],
            'resource_type.name' => ['required_with:resource_type', 'string', 'max:255'],
            'resource_type.description' => ['nullable', 'string'],
        ];
    }
}
