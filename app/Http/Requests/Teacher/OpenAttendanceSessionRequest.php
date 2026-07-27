<?php

namespace App\Http\Requests\Teacher;

use App\Models\AttendanceSession;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OpenAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AttendanceSession::class) === true;
    }

    public function rules(): array
    {
        return [
            'learning_session_id' => [
                'required', 'integer', 'exists:learning_sessions,id',
                Rule::unique('attendance_sessions')->where('class_id', $this->input('class_id')),
            ],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['learning_session_id.unique' => 'Sesi presensi untuk pertemuan dan kelas ini sudah tersedia.'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $learningSession = LearningSession::query()->find($this->integer('learning_session_id'));
            $schoolClass = SchoolClass::query()->find($this->integer('class_id'));
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());

            if ($learningSession && $schoolClass && $learningSession->academic_year_id !== $schoolClass->academic_year_id) {
                $validator->errors()->add('class_id', 'Pertemuan dan kelas harus berada pada tahun ajaran yang sama.');
            }

            if ($learningSession && $schoolClass && collect([$learningSession->program_batch_id, $schoolClass->program_batch_id])->filter()->unique()->count() > 1) {
                $validator->errors()->add('class_id', 'Pertemuan dan kelas harus berasal dari program yang sama.');
            }

            if ($activeBatchId && (($learningSession?->program_batch_id && $learningSession->program_batch_id !== $activeBatchId) || ($schoolClass?->program_batch_id && $schoolClass->program_batch_id !== $activeBatchId))) {
                $validator->errors()->add('class_id', 'Pertemuan dan kelas harus berasal dari program aktif.');
            }

            if ($schoolClass && ! $schoolClass->classMemberships()->when($schoolClass->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'active')->exists()) {
                $validator->errors()->add('class_id', 'Kelas belum memiliki siswa aktif untuk dipresensi.');
            }
        }];
    }
}
