<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KnowledgeResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleSlug::SuperAdmin) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('knowledge_resources')->ignore($this->route('knowledge_resource'))],
            'category' => ['required', 'string', 'max:80'],
            'content_type' => ['required', Rule::in(['ebook', 'article', 'guide', 'video'])],
            'thumbnail_url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:900'],
            'resource_url' => ['required', 'url', 'max:2048'],
            'display_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_featured' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('title')),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
