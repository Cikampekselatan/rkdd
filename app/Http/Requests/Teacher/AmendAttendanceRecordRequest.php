<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmendAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('attendance_record');

        return $record instanceof AttendanceRecord && $this->user()?->can('amend', $record) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
