<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QualificationLevel;
use App\Models\TaskRequirement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequirementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaskRequirement::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required_without:task', 'integer', 'exists:tasks,id', 'prohibits:task'],
            'task' => ['required_without:task_id', 'array', 'prohibits:task_id'],
            'task.title' => ['required_with:task', 'string', 'max:255'],
            'task.description' => ['nullable', 'string'],
            'task.starts_at' => ['required_with:task', 'date'],
            'task.ends_at' => ['required_with:task', 'date', 'after_or_equal:task.starts_at'],
            'task.effort_value' => ['required_with:task', 'numeric', 'min:0'],
            'task.effort_unit' => ['required_with:task', 'string', 'max:255'],
            'task.priority' => ['required_with:task', 'string', 'max:255'],
            'task.status' => ['required_with:task', 'string', 'max:255'],
            'qualification_id' => ['required_without:qualification', 'integer', 'exists:qualifications,id', 'prohibits:qualification'],
            'qualification' => ['required_without:qualification_id', 'array', 'prohibits:qualification_id'],
            'qualification.name' => ['required_with:qualification', 'string', 'max:255'],
            'qualification.description' => ['nullable', 'string'],
            'qualification.resource_type_id' => ['exclude_without:qualification', 'nullable', 'integer', 'exists:resource_types,id', 'prohibits:qualification.resource_type'],
            'qualification.resource_type' => ['exclude_without:qualification', 'array', 'prohibits:qualification.resource_type_id'],
            'qualification.resource_type.name' => ['required_with:qualification.resource_type', 'string', 'max:255'],
            'qualification.resource_type.description' => ['nullable', 'string'],
            'required_level' => ['nullable', Rule::enum(QualificationLevel::class)],
        ];
    }
}
