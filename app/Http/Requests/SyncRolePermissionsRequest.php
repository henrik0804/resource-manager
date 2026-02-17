<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccessSection;
use App\Models\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncRolePermissionsRequest extends FormRequest
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
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'array'],
            'permissions.*.can_read' => ['required', 'boolean'],
            'permissions.*.can_write' => ['required', 'boolean'],
            'permissions.*.can_write_owned' => ['required', 'boolean'],
        ];
    }

    /**
     * Validate the permission keys are valid AccessSection values.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $validSections = collect(AccessSection::cases())->pluck('value')->all();

            foreach (array_keys($this->input('permissions', [])) as $section) {
                if (! in_array($section, $validSections, true)) {
                    $validator->errors()->add(
                        "permissions.{$section}",
                        "The section '{$section}' is not a valid access section.",
                    );
                }
            }
        });
    }
}
