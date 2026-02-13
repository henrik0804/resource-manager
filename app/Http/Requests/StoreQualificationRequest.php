<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQualificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'resource_type_id' => ['nullable', 'integer', 'exists:resource_types,id', 'prohibits:resource_type'],
            'resource_type' => ['array', 'prohibits:resource_type_id'],
            'resource_type.name' => ['required_with:resource_type', 'string', 'max:255'],
            'resource_type.description' => ['nullable', 'string'],
        ];
    }
}
