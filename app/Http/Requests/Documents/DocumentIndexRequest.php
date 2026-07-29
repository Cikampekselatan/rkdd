<?php

namespace App\Http\Requests\Documents;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Models\DocumentResource;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', DocumentResource::class) === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(DocumentCategory::class)],
            'audience' => ['nullable', Rule::enum(DocumentAudience::class)],
            'academic_year_id' => ['nullable', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())],
            'semester' => ['nullable', Rule::in([1, 2])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'pinned' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('pinned')) {
            $this->merge(['pinned' => $this->boolean('pinned')]);
        }
    }
}
