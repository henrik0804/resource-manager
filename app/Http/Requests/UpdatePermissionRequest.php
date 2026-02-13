<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccessSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->permission) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permission = $this->route('permission');
        $roleId = $this->input('role_id', $permission?->role_id);

        return [
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'section' => [
                'sometimes',
                Rule::enum(AccessSection::class),
                Rule::unique('permissions')
                    ->where('role_id', $roleId)
                    ->ignore($permission?->id),
            ],
            'can_read' => ['sometimes', 'boolean'],
            'can_write' => ['sometimes', 'boolean'],
            'can_write_owned' => ['sometimes', 'boolean'],
        ];
    }
}
