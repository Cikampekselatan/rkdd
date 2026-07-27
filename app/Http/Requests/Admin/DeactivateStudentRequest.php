<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentExitReason;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeactivateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStudentStatus', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'exit_reason' => ['required', Rule::enum(StudentExitReason::class)],
            'exit_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
