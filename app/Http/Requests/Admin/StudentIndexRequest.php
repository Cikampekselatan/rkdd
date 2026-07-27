<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewStudents', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_map(fn (UserStatus $status): string => $status->value, UserStatus::cases()))],
            'class_id' => ['nullable', 'exists:classes,id'],
            'grade_level' => ['nullable', 'integer', Rule::in([7, 8, 9])],
            'interest' => ['nullable', Rule::in(['design', 'photography', 'video', 'presentation', 'ai', 'coding', 'data', 'entrepreneurship'])],
            'onboarding' => ['nullable', Rule::in(['complete', 'incomplete'])],
        ];
    }
}
