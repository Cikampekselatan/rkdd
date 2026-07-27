<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear
            ? $this->user()?->can('update', $academicYear) === true
            : $this->user()?->can('create', AcademicYear::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_years')->ignore($this->route('academic_year'))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
