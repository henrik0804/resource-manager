<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ResourceAbsence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceAbsenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ResourceAbsence::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_id' => ['required_without:resource', 'integer', 'exists:resources,id', 'prohibits:resource'],
            'resource' => ['required_without:resource_id', 'array', 'prohibits:resource_id'],
            'resource.name' => ['required_with:resource', 'string', 'max:255'],
            'resource.resource_type_id' => ['exclude_without:resource', 'required_without:resource.resource_type', 'integer', 'exists:resource_types,id', 'prohibits:resource.resource_type'],
            'resource.resource_type' => ['exclude_without:resource', 'required_without:resource.resource_type_id', 'array', 'prohibits:resource.resource_type_id'],
            'resource.resource_type.name' => ['required_with:resource.resource_type', 'string', 'max:255'],
            'resource.resource_type.description' => ['nullable', 'string'],
            'resource.capacity_value' => ['nullable', 'numeric', 'min:0'],
            'resource.capacity_unit' => ['nullable', 'string', 'max:255'],
            'resource.user_id' => ['nullable', 'integer', 'exists:users,id', 'prohibits:resource.user'],
            'resource.user' => ['array', 'prohibits:resource.user_id'],
            'resource.user.name' => ['required_with:resource.user', 'string', 'max:255'],
            'resource.user.email' => ['required_with:resource.user', 'string', 'email', 'max:255', 'unique:users,email'],
            'resource.user.password' => ['required_with:resource.user', 'string', 'min:8'],
            'resource.user.role_id' => ['exclude_without:resource.user', 'required_without:resource.user.role', 'integer', 'exists:roles,id', 'prohibits:resource.user.role'],
            'resource.user.role' => ['exclude_without:resource.user', 'required_without:resource.user.role_id', 'array', 'prohibits:resource.user.role_id'],
            'resource.user.role.name' => ['required_with:resource.user.role', 'string', 'max:255'],
            'resource.user.role.description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'recurrence_rule' => ['nullable', 'string'],
        ];
    }
}
