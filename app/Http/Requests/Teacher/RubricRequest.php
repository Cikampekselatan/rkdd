<?php

namespace App\Http\Requests\Teacher;

use App\Models\Rubric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        $r = $this->route('rubric');

        return $r ? $this->user()?->can('update', $r) === true : $this->user()?->can('create', Rubric::class) === true;
    }

    public function rules(): array
    {
        return ['academic_year_id' => ['nullable', 'exists:academic_years,id'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'is_active' => ['nullable', 'boolean'], 'criteria' => ['required', 'array', 'min:1', 'max:8'], 'criteria.*.name' => ['required', 'string', 'max:255'], 'criteria.*.description' => ['nullable', 'string', 'max:2000'], 'criteria.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'], 'criteria.*.levels' => ['required', 'array', 'size:4'], 'criteria.*.levels.*' => ['required', 'string', 'max:2000']];
    }

    public function after(): array
    {
        return [function (Validator $v): void {
            $sum = collect($this->input('criteria', []))->sum(fn ($c) => (float) ($c['weight'] ?? 0));
            if (abs($sum - 100) > 0.001) {
                $v->errors()->add('criteria', 'Total bobot kriteria wajib tepat 100%.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
