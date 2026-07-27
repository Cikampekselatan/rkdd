<?php

namespace App\Http\Requests;

use App\Models\DiscussionTopic;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DiscussionTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DiscussionTopic::class) === true;
    }

    public function rules(): array
    {
        return ['class_id' => [$this->user()?->isStaff() ? 'required' : 'nullable', 'exists:classes,id'], 'learning_session_id' => ['nullable', 'exists:learning_sessions,id'], 'title' => ['required', 'string', 'min:5', 'max:255'], 'body' => ['required', 'string', 'min:10', 'max:20000']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
            $classId = $this->user()?->isStaff()
                ? $this->integer('class_id')
                : app(ProgramContextService::class)->studentActiveMembership($this->user())?->class_id;
            $class = SchoolClass::find($classId);

            if (! $class) {
                return;
            }

            if ($activeBatchId && $class->program_batch_id && $class->program_batch_id !== $activeBatchId) {
                $validator->errors()->add('class_id', 'Kelas harus berasal dari program aktif.');
            }

            if ($this->filled('learning_session_id')) {
                $session = LearningSession::find($this->integer('learning_session_id'));
                if ($session && $class->academic_year_id !== $session->academic_year_id) {
                    $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari tahun ajaran kelas yang dipilih.');
                }
                if ($session && $activeBatchId && $session->program_batch_id && $session->program_batch_id !== $activeBatchId) {
                    $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari program aktif.');
                }
                if ($session && $class->program_batch_id && $session->program_batch_id && $class->program_batch_id !== $session->program_batch_id) {
                    $validator->errors()->add('learning_session_id', 'Pertemuan harus berasal dari program yang sama dengan kelas.');
                }
            }
        }];
    }
}
