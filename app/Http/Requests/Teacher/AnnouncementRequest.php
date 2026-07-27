<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Models\Announcement;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('announcement');

        return $item ? $this->user()?->can('update', $item) === true : $this->user()?->can('create', Announcement::class) === true;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:30000'], 'audience' => ['required', Rule::enum(AnnouncementAudience::class)], 'priority' => ['required', Rule::enum(AnnouncementPriority::class)], 'academic_year_id' => ['nullable', 'exists:academic_years,id'], 'class_id' => ['nullable', 'required_if:audience,class', 'exists:classes,id'], 'learning_session_id' => ['nullable', 'required_if:audience,session', 'exists:learning_sessions,id'], 'published_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:published_at'], 'is_pinned' => ['nullable', 'boolean'], 'action' => ['required', Rule::in(['draft', 'publish'])]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $yearId = $this->integer('academic_year_id') ?: null;
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
            $class = $this->filled('class_id') ? SchoolClass::find($this->integer('class_id')) : null;
            $session = $this->filled('learning_session_id') ? LearningSession::find($this->integer('learning_session_id')) : null;

            if ($this->input('audience') === 'class') {
                if ($yearId && $class && $class->academic_year_id !== $yearId) {
                    $validator->errors()->add('class_id', 'Kelas harus berasal dari tahun ajaran yang dipilih.');
                }
                if ($activeBatchId && $class && $class->program_batch_id && $class->program_batch_id !== $activeBatchId) {
                    $validator->errors()->add('class_id', 'Kelas harus berasal dari program aktif.');
                }
            }

            if ($this->input('audience') === 'session') {
                if ($yearId && $session && $session->academic_year_id !== $yearId) {
                    $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari tahun ajaran yang dipilih.');
                }
                if ($activeBatchId && $session && $session->program_batch_id && $session->program_batch_id !== $activeBatchId) {
                    $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari program aktif.');
                }
            }

            if ($class && $session && $class->program_batch_id && $session->program_batch_id && $class->program_batch_id !== $session->program_batch_id) {
                $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari program yang sama dengan kelas.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_pinned' => $this->boolean('is_pinned')]);
    }
}
