<?php

namespace App\Http\Requests\Teacher;

use App\Enums\LearningMaterialType;
use App\Models\LearningSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('learning_material');
        $session = $this->route('learning_session');

        return $material
            ? $this->user()?->can('update', $material) === true
            : $session instanceof LearningSession && $this->user()?->can('update', $session) === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LearningMaterialType::class)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:50000', 'required_if:type,'.LearningMaterialType::Text->value],
            'url' => ['nullable', 'url:http,https', 'max:2048', 'required_unless:type,'.LearningMaterialType::Text->value],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_required' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'content' => $this->filled('content') ? $this->input('content') : null,
            'url' => $this->filled('url') ? $this->input('url') : null,
            'is_required' => $this->boolean('is_required'),
        ]);
    }
}
