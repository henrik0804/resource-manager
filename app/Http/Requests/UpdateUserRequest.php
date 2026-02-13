<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
            'role_id' => ['sometimes', 'integer', 'exists:roles,id', 'prohibits:role'],
            'role' => ['sometimes', 'array', 'prohibits:role_id'],
            'role.name' => ['required_with:role', 'string', 'max:255'],
            'role.description' => ['nullable', 'string'],
        ];
    }
}
