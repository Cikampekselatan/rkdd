<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentType;
use App\Models\Assignment;
use App\Models\LearningSession;
use App\Models\Rubric;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $a = $this->route('assignment');

        return $a ? $this->user()?->can('update', $a) === true : $this->user()?->can('create', Assignment::class) === true;
    }

    public function rules(): array
    {
        return ['academic_year_id' => ['required', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())], 'class_id' => ['required', 'exists:classes,id'], 'learning_session_id' => ['required', 'exists:learning_sessions,id'], 'rubric_id' => ['nullable', 'exists:rubrics,id'], 'title' => ['required', 'string', 'max:255'], 'instructions' => ['required', 'string', 'max:30000'], 'questions' => ['nullable', 'array', 'max:20'], 'questions.*.prompt' => ['nullable', 'string', 'max:1000'], 'questions.*.help_text' => ['nullable', 'string', 'max:2000'], 'questions.*.answer_type' => ['required_with:questions.*.prompt', Rule::enum(AssignmentQuestionType::class)], 'questions.*.options_text' => ['nullable', 'string', 'max:5000'], 'questions.*.options' => ['nullable', 'array'], 'questions.*.options.*' => ['string', 'max:255'], 'questions.*.is_required' => ['nullable', 'boolean'], 'type' => ['required', Rule::enum(AssignmentType::class)], 'available_from' => ['nullable', 'date'], 'due_at' => ['required', 'date'], 'allow_late' => ['nullable', 'boolean'], 'max_files' => ['required', 'integer', 'min:0', 'max:10'], 'max_file_size_kb' => ['required', 'integer', 'min:100', 'max:20480'], 'allowed_mime_types_text' => ['nullable', 'string', 'max:1000'], 'allowed_mime_types' => ['nullable', 'array'], 'allowed_mime_types.*' => ['string', 'max:100'], 'max_revisions' => ['required', 'integer', 'min:0', 'max:5'], 'is_published' => ['nullable', 'boolean']];
    }

    public function after(): array
    {
        return [function (Validator $v): void {
            $s = LearningSession::find($this->integer('learning_session_id'));
            $c = SchoolClass::find($this->integer('class_id'));
            $r = $this->filled('rubric_id') ? Rubric::find($this->integer('rubric_id')) : null;
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
            if (($s && $s->academic_year_id !== $this->integer('academic_year_id')) || ($c && $c->academic_year_id !== $this->integer('academic_year_id'))) {
                $v->errors()->add('academic_year_id', 'Pertemuan dan kelas harus berasal dari tahun ajaran yang sama.');
            }
            if ($r?->academic_year_id && $r->academic_year_id !== $this->integer('academic_year_id')) {
                $v->errors()->add('rubric_id', 'Rubrik harus berasal dari tahun ajaran yang sama dengan tugas.');
            }
            if ($activeBatchId && (($s?->program_batch_id && $s->program_batch_id !== $activeBatchId) || ($c?->program_batch_id && $c->program_batch_id !== $activeBatchId) || ($r?->program_batch_id && $r->program_batch_id !== $activeBatchId))) {
                $v->errors()->add('class_id', 'Kelas, pertemuan, dan rubrik harus berasal dari program aktif.');
            }
            $programBatchIds = collect([$s?->program_batch_id, $c?->program_batch_id, $r?->program_batch_id])->filter()->unique();
            if ($programBatchIds->count() > 1) {
                $v->errors()->add('program_batch_id', 'Kelas, pertemuan, dan rubrik harus berasal dari program yang sama.');
            }
            if ($this->filled('available_from') && $this->date('due_at')?->lessThanOrEqualTo($this->date('available_from'))) {
                $v->errors()->add('due_at', 'Tenggat harus setelah waktu mulai tersedia.');
            }
            foreach ($this->input('questions', []) as $index => $question) {
                if (($question['answer_type'] ?? null) === AssignmentQuestionType::MultipleChoice->value && count($question['options'] ?? []) < 2) {
                    $v->errors()->add("questions.$index.options_text", 'Pilihan ganda minimal memiliki 2 opsi jawaban.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $m = collect(explode(',', (string) $this->input('allowed_mime_types_text')))->map(fn ($v) => trim($v))->filter()->values()->all();
        $questions = collect($this->input('questions', []))
            ->map(fn (array $question): array => [
                ...$question,
                'prompt' => trim((string) ($question['prompt'] ?? '')),
                'options' => collect(preg_split('/\r\n|\r|\n/', (string) ($question['options_text'] ?? '')))
                    ->map(fn (string $option): string => trim($option))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'is_required' => filter_var($question['is_required'] ?? false, FILTER_VALIDATE_BOOL),
            ])
            ->filter(fn (array $question): bool => filled($question['prompt']))
            ->values()
            ->all();
        $this->merge(['allow_late' => $this->boolean('allow_late'), 'is_published' => $this->boolean('is_published'), 'allowed_mime_types' => $m, 'questions' => $questions]);
    }
}
