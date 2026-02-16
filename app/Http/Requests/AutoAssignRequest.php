<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccessSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AutoAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWriteSection(AccessSection::AutomatedAssignment) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
