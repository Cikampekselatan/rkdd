<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProgramBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleSlug::SuperAdmin) === true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->whereNull('deleted_at')],
            'institution_id' => ['required', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('program_batches')->ignore($this->route('program_batch'))],
            'period_label' => ['required', 'string', 'max:80'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'audience_type' => ['required', 'string', 'max:80'],
            'participant_label' => ['required', 'string', 'max:80'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
