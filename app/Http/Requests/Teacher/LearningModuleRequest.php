<?php

namespace App\Http\Requests\Teacher;

use App\Models\LearningModule;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = $this->route('learning_module');

        return $module
            ? $this->user()?->can('update', $module) === true
            : $this->user()?->can('create', LearningModule::class) === true;
    }

    public function rules(): array
    {
        $module = $this->route('learning_module');
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());

        return [
            'academic_year_id' => ['required', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())],
            'module_number' => [
                'required', 'integer', 'min:1', 'max:15',
                Rule::unique('learning_modules')
                    ->withoutTrashed()
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->when($activeBatchId, fn ($rule) => $rule->where('program_batch_id', $activeBatchId))
                    ->ignore($module),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
