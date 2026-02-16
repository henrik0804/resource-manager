<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TaskAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConflictResolutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaskAssignment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_resource_id' => ['required', 'integer', 'exists:resources,id'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'allocation_ratio' => ['nullable', 'numeric', 'min:0'],
            'exclude_assignment_id' => ['nullable', 'integer', 'exists:task_assignments,id'],
        ];
    }
}
