<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
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
            'resource_type_id' => ['required_without:resource_type', 'integer', 'exists:resource_types,id', 'prohibits:resource_type'],
            'resource_type' => ['required_without:resource_type_id', 'array', 'prohibits:resource_type_id'],
            'resource_type.name' => ['required_with:resource_type', 'string', 'max:255'],
            'resource_type.description' => ['nullable', 'string'],
            'capacity_value' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'prohibits:user'],
            'user' => ['array', 'prohibits:user_id'],
            'user.name' => ['required_with:user', 'string', 'max:255'],
            'user.email' => ['required_with:user', 'string', 'email', 'max:255', 'unique:users,email'],
            'user.password' => ['required_with:user', 'string', 'min:8'],
            'user.role_id' => ['exclude_without:user', 'required_without:user.role', 'integer', 'exists:roles,id', 'prohibits:user.role'],
            'user.role' => ['exclude_without:user', 'required_without:user.role_id', 'array', 'prohibits:user.role_id'],
            'user.role.name' => ['required_with:user.role', 'string', 'max:255'],
            'user.role.description' => ['nullable', 'string'],
        ];
    }
}
