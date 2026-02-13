<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskAssignmentRequest extends FormRequest
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
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'allocation_ratio' => ['nullable', 'numeric', 'min:0'],
            'assignment_source' => ['required', 'string', 'max:255'],
            'assignee_status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
