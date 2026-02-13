<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccessSection;
use App\Models\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Permission::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'section' => [
                'required',
                Rule::enum(AccessSection::class),
                Rule::unique('permissions')->where('role_id', $this->input('role_id')),
            ],
            'can_read' => ['required', 'boolean'],
            'can_write' => ['required', 'boolean'],
            'can_write_owned' => ['required', 'boolean'],
        ];
    }
}
