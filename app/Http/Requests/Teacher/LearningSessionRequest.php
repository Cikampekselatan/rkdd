<?php

namespace App\Http\Requests\Teacher;

use App\Enums\LearningSessionStatus;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('learning_session');

        return $session
            ? $this->user()?->can('update', $session) === true
            : $this->user()?->can('create', LearningSession::class) === true;
    }

    public function rules(): array
    {
        $session = $this->route('learning_session');
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
        $statuses = [
            LearningSessionStatus::Draft->value,
            LearningSessionStatus::Scheduled->value,
            LearningSessionStatus::Ongoing->value,
            LearningSessionStatus::Completed->value,
            LearningSessionStatus::Archived->value,
        ];

        if ($session?->status === LearningSessionStatus::Published) {
            $statuses[] = LearningSessionStatus::Published->value;
        }

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'learning_module_id' => ['required', 'exists:learning_modules,id'],
            'session_number' => [
                'required', 'integer', 'min:1', 'max:255',
                Rule::unique('learning_sessions')
                    ->withoutTrashed()
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->when($activeBatchId, fn ($rule) => $rule->where('program_batch_id', $activeBatchId))
                    ->ignore($session),
            ],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240'],
            'objectives' => ['required', 'array', 'min:1', 'max:10'],
            'objectives.*' => ['required', 'string', 'max:500'],
            'introduction' => ['nullable', 'string', 'max:20000'],
            'summary' => ['nullable', 'string', 'max:20000'],
            'practice_instructions' => ['nullable', 'string', 'max:20000'],
            'reflection_prompt' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in($statuses)],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,'.LearningSessionStatus::Scheduled->value],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $module = LearningModule::query()->find($this->input('learning_module_id'));

            if ($module && (int) $module->academic_year_id !== (int) $this->input('academic_year_id')) {
                $validator->errors()->add('learning_module_id', 'Modul harus berasal dari tahun ajaran yang dipilih.');
            }

            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
            if ($activeBatchId && $module?->program_batch_id && $module->program_batch_id !== $activeBatchId) {
                $validator->errors()->add('learning_module_id', 'Modul harus berasal dari program aktif.');
            }

            if (
                $this->input('status') === LearningSessionStatus::Scheduled->value
                && $this->date('scheduled_at')?->isPast()
            ) {
                $validator->errors()->add('scheduled_at', 'Jadwal publikasi harus berada di masa mendatang.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $objectives = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('objectives_text')))
            ->map(fn (string $objective): string => trim($objective))
            ->filter()
            ->values()
            ->all();

        $this->merge([
            'objectives' => $objectives,
            'scheduled_at' => $this->filled('scheduled_at') ? $this->input('scheduled_at') : null,
        ]);
    }
}
