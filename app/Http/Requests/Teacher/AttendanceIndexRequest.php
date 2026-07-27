<?php

namespace App\Http\Requests\Teacher;

use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AttendanceSession::class) === true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('academic_year_id') || ! $this->filled('class_id')) {
                return;
            }

            $matches = SchoolClass::query()
                ->whereKey($this->integer('class_id'))
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->exists();

            if (! $matches) {
                $validator->errors()->add('class_id', 'Kelas tidak berasal dari tahun ajaran yang dipilih.');
            }
        }];
    }
}
