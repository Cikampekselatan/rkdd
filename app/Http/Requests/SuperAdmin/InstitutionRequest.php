<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleSlug::SuperAdmin) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('institutions')->ignore($this->route('institution'))],
            'type' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1500'],
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
