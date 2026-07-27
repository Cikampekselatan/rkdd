<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\StudentMembershipStatus;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveBulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('attendance_session');

        return $session instanceof AttendanceSession && $this->user()?->can('update', $session) === true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $session = $this->route('attendance_session');
            if (! $session instanceof AttendanceSession) {
                return;
            }

            $submitted = collect($this->input('records', []))->pluck('user_id')->map(fn ($id) => (int) $id)->sort()->values();
            $members = ClassStudent::query()
                ->where('academic_year_id', $session->academic_year_id)
                ->where('class_id', $session->class_id)
                ->when($session->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('status', StudentMembershipStatus::Active->value)
                ->pluck('user_id')->map(fn ($id) => (int) $id)->sort()->values();

            if ($submitted->all() !== $members->all()) {
                $validator->errors()->add('records', 'Data presensi harus memuat tepat seluruh siswa aktif di kelas ini.');
            }
        }];
    }
}
