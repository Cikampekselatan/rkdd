<?php

namespace App\Http\Requests\Student;

use App\Enums\PortfolioVisibility;
use App\Models\Grade;
use App\Models\PortfolioItem;
use App\Services\PortfolioWorkTypeOptionService;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PortfolioItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('portfolio_item');

        return $item ? $this->user()?->can('update', $item) === true : $this->user()?->can('create', PortfolioItem::class) === true;
    }

    public function rules(): array
    {
        $item = $this->route('portfolio_item');

        return [
            'source_type' => ['required', Rule::in(['graded', 'independent'])],
            'submission_id' => ['nullable', 'required_if:source_type,graded', 'exists:submissions,id', Rule::unique('portfolio_items', 'submission_id')->ignore($item?->id)],
            'title' => ['required', 'string', 'max:255'],
            'work_type' => ['required', 'string', Rule::in($this->availableWorkTypeSlugs())],
            'description' => ['required', 'string', 'max:20000'],
            'reflection' => ['nullable', 'string', 'max:20000'],
            'sources' => ['nullable', 'string', 'max:5000'],
            'ai_used' => ['nullable', 'boolean'],
            'ai_tools' => ['nullable', Rule::requiredIf($this->boolean('ai_used')), 'string', 'max:1000'],
            'ai_usage_description' => ['nullable', Rule::requiredIf($this->boolean('ai_used')), 'string', 'max:5000'],
            'visibility' => ['required', Rule::enum(PortfolioVisibility::class)],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'initial_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,ppt,pptx,zip,mp4', 'max:20480'],
            'final_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,ppt,pptx,zip,mp4', 'max:20480'],
            'initial_url' => ['nullable', 'url:http,https', 'max:2048'],
            'final_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('source_type') === 'graded') {
                $grade = Grade::query()->where('is_published', true)->whereHas('submission', fn ($query) => $query->whereKey($this->integer('submission_id'))->where('user_id', $this->user()?->id))->first();

                if (! $grade) {
                    $validator->errors()->add('submission_id', 'Hanya submission milik sendiri dengan nilai published yang dapat dipilih.');
                }
            } else {
                $item = $this->route('portfolio_item');
                $hasExistingFinal = $item?->source_type === 'independent' && ($item->final_file_path || $item->final_url);

                if (! $this->hasFile('final_file') && ! $this->filled('final_url') && ! $hasExistingFinal) {
                    $validator->errors()->add('final_file', 'Karya mandiri membutuhkan file atau URL versi final.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ai_used' => $this->boolean('ai_used')]);
    }

    /**
     * @return array<int, string>
     */
    private function availableWorkTypeSlugs(): array
    {
        $item = $this->route('portfolio_item');
        $activeBatch = app(ProgramContextService::class)->activeBatch($this->user());
        $slugs = app(PortfolioWorkTypeOptionService::class)
            ->activeFor($activeBatch?->program)
            ->pluck('slug')
            ->all();

        if ($item?->work_type && ! in_array($item->work_type, $slugs, true)) {
            $slugs[] = $item->work_type;
        }

        return $slugs;
    }
}
